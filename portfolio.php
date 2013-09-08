<?php
class Portfolio extends Feathers implements Feather {

  public function __init() {
    $this->setField(array("attr" => "title",
                          "type" => "text",
                         "label" => __("Title", "portfolio")));
    $this->setField(array("attr" => "description",
                          "type" => "text",
                      "optional" => true,
                         "label" => __("Description", "portfolio")));
    $this->setField(array("attr" => "preview",
                          "type" => "file",
                      "optional" => true,
                         "label" => __("Preview", "portfolio"),
                          "note" => "<small>(Max filesize: ".ini_get('upload_max_filesize').")</small>"));
    $this->setField(array("attr" => "link",
                          "type" => "text",
                         "label" => __("Link", "portfolio"),
                      "optional" => true));
    $this->setField(array("attr" => "body",
                          "type" => "text_block",
                         "label" => __("Body", "portfolio")));
    $this->setField(array("attr" => "people",
                          "type" => "text",
                      "optional" => true,
                         "label" => __("Author", "portfolio"),
                      "optional" => true));
    $this->setField(array("attr" => "gallery",
                          "type" => "file",
                      "optional" => true,
                         "class" => "image-download",
                      "multiple" => "true",
                         "label" => __("Files", "portfolio"),
                          "note" => "<small>(Max filesize: ".ini_get('upload_max_filesize').")</small>"));

    $this->setFilter("title", array("markup_title", "markup_post_title"));
    $this->setFilter("link", array("markup_title", "markup_post_title"));
    $this->setFilter("people", array("markup_title", "markup_post_title"));
    $this->setFilter("body", array("markup_text", "markup_post_title"));
    $this->setFilter("description", array("markup_title", "markup_post_text"));

    $this->respondTo("delete_post", "delete_gallery");
    $this->respondTo("delete_post", "delete_preview");
  }

  public function submit() {
    $config = Config::current();

    if (empty($_POST['title']))
      error(__("Error"), __("Title can't be blank."));
    if (empty($_POST['body']))
      error(__("Error"), __("Body can't be blank."));

    if (isset($_FILES['preview']) and $_FILES['preview']['error'] == 0)
      $preview = upload($_FILES['preview'], array("jpg", "jpeg", "png", "gif", "bmp"));

    if (isset($_FILES['gallery']) and $_FILES['gallery']['error'] == 0) 
      $files  = array(); $gallery = array();
 
      foreach($_FILES as $name=>$file) {
        if(is_array($file['name'])){
            $count = count($file['name']);
              for($i=0; $i < $count; $i++) {
                $files[$name][$i] = array('name' => $file['name'][$i],
                                          'type' => $file['type'][$i],
                                      'tmp_name' => $file['tmp_name'][$i],
                                         'error' => $file['error'][$i],
                                          'size' => $file['size'][$i]);
              }
        } else {
          $files[$name] = $file;
        }
      }
      foreach ($files['gallery'] as $file) {
          $result = upload($file, array("jpg", "jpeg", "png", "gif", "bmp"));
          $gallery[] = $result;
      }
      $gallery = serialize($gallery);

      fallback($_POST['slug'], sanitize($_POST['title']));

      return Post::add(array("title" => $_POST['title'],
                       "description" => $_POST['description'],
                              "link" => $_POST['link'],
                            "people" => $_POST['people'],
                              "body" => $_POST['body'],
                           "preview" => $config->chyrp_url.$config->uploads_path.$preview,
                           "gallery" => $gallery),
                            $_POST['slug'],
                            Post::check_url($_POST['slug']));
  }

  public function update($post) {
    $config = Config::current();

    if (empty($_POST['title']))
      error(__("Error"), __("Title can't be blank."));
    if (empty($_POST['body']))
      error(__("Error"), __("Body can't be blank."));

    if (isset($_FILES['gallery'])) {
      $this->delete_gallery($post);
      $files  = array(); $gallery = array();
        foreach($_FILES as $name=>$file) {
            if(is_array($file['name'])){
              $count = count($file['name']);
                for($i=0; $i < $count; $i++) {
                  $files[$name][$i] = array('name' => $file['name'][$i],
                                            'type' => $file['type'][$i],
                                        'tmp_name' => $file['tmp_name'][$i],
                                           'error' => $file['error'][$i],
                                            'size' => $file['size'][$i]);
                }
            } else {
              $files[$name] = $file;
            }
        }
        foreach ($files['gallery'] as $file) {                      
          $gallery[] = upload($file, array("jpg", "jpeg", "png", "gif", "bmp"));
        }
      $gallery = serialize($gallery);
  } else {
      $gallery = $post->gallery;
  }

    if (isset($_FILES['preview']) and $_FILES['preview']['error'] == 0) {
      $this->delete_preview($post);
      $preview = upload($_FILES['preview'], array("jpg", "jpeg", "png", "gif", "tiff", "bmp"));
    } else
      $preview = $post->preview;


  $post->update(array("title" => $_POST['title'],
                "description" => $_POST['description'],
                       "link" => $_POST['link'],
                     "people" => $_POST['people'],
                       "body" => $_POST['body'],
                    "preview" => $config->chyrp_url.$config->uploads_path.$preview,
                    "gallery" => $gallery));
  }

  public function title($post) {
    return $post->title;
  }

  public function excerpt($post) {
    return $post->description;
  }
 
  public function unserialize($array) {
    return unserialize($array);
  }

  public function image_list($array) {
    $config = Config::current();
    $files = unserialize($array);
    $imagelist = "\n";
    foreach ($files as $file) {
      $imagelist .= '<img src="'.$config->chyrp_url.$config->uploads_path.$file.'">'."\n";
    }
  return $imagelist;
  }

  public function feed_content($post) {
    $body = "<h1>";
    $body.= $post->title;
    $body.= "</h1>\n";
    $body.= $post->description;
    return $body;
  }

  public function delete_gallery($post) {
    if ($post->feather != "portfolio") return;
    $array = unserialize($post->gallery);
      foreach ($array as $value) {
        unlink(MAIN_DIR.Config::current()->uploads_path.$value);
      }
  }

  public function delete_preview($post) {
    unlink(MAIN_DIR.Config::current()->uploads_path.$post->preview);
  }
}
