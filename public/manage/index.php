<?php
  function transliterate_ru($textcyr = null, $textlat = null) {
      $cyr = array(
      'ж',  'ч',  'щ',   'ш',  'ю',  'а', 'б', 'в', 'г', 'д', 'е', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п', 'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ъ', 'ь', 'я', ' ',
      'Ж',  'Ч',  'Щ',   'Ш',  'Ю',  'А', 'Б', 'В', 'Г', 'Д', 'Е', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ъ', 'Ь', 'Я', ' ');
      $lat = array(
      'zh', 'ch', 'sht', 'sh', 'yu', 'a', 'b', 'v', 'g', 'd', 'e', 'z', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'r', 's', 't', 'u', 'f', 'h', 'c', 'y', '', 'ya', '_',
      'Zh', 'Ch', 'Sht', 'Sh', 'Yu', 'A', 'B', 'V', 'G', 'D', 'E', 'Z', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'R', 'S', 'T', 'U', 'F', 'H', 'c', 'Y', '', 'ya', '_');
      if($textcyr) return str_replace($cyr, $lat, $textcyr);
      else if($textlat) return str_replace($lat, $cyr, $textlat);
      else return null;
  }

  function augment_group($group_id, $filename) {
    $data = json_decode(file_get_contents($filename));

    $data->metadata->id = $group_id;

    foreach ($data->features as $feature) {
      if ($feature->properties->description) {
        $result = explode("\n", $feature->properties->description);
        $feature->properties->previewSrc = $result[1];
        $feature->properties->articleHref = $result[0];
      }
    }

    foreach ($data->features as $feature) {
      $feature->properties->group = $group_id;
    }

    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  }

  function handle_new_group() {
    $name = filter_var($_POST['group_name'], FILTER_SANITIZE_STRING);
    $id = transliterate_ru(mb_strtolower($name));

    $features = json_decode(file_get_contents($_FILES['group_file']['tmp_name']))->features;

    move_uploaded_file($_FILES['group_icon']['tmp_name'], "../icons/$id.png");
    move_uploaded_file($_FILES['group_file']['tmp_name'], "../data/$id.json");

    augment_group($id, "../data/$id.json");

    return array(
      'id' => $id,
      'name' => $name,
      'iconHref' => "/map/icons/$id.png",
      'className' => $id . '_links',
      'count' => count($features)
    );
  }

  function handle_update_group() {
    $name = filter_var($_POST['group_name'], FILTER_SANITIZE_STRING);
    $id = filter_var($_POST['group_id'], FILTER_SANITIZE_STRING);
    $count = false;

    if (file_exists($_FILES['group_file']['tmp_name'])) {
      $features = json_decode(file_get_contents($_FILES['group_file']['tmp_name']))->features;

      move_uploaded_file($_FILES['group_file']['tmp_name'], "../data/$id.json");

      augment_group($id, "../data/$id.json");
    
      $count = count($features);
    }

    if (file_exists($_FILES['group_icon']['tmp_name'])) {
      move_uploaded_file($_FILES['group_icon']['tmp_name'], "../icons/$id.png");
    }

    if ($count) {
      return array(
        'id' => $id,
        'name' => $name,
        'count' => $count
      );
    } else {
      return array(
        'id' => $id,
        'name' => $name
      );
    }
  }

  function update_group($newGroup) {
    $groups = json_decode(file_get_contents('../data/groups.json'));

    foreach ($groups->groups as $group) {
      if ($group->id === $newGroup['id']) {
        $group->name = $newGroup['name'];
        if (isset($newGroup['count'])) {
          $group->count = $newGroup['count'];
        }
        break;
      }
    }

    file_put_contents("../data/groups.json", json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  }

  function delete_group($id) {
    $groups = json_decode(file_get_contents('../data/groups.json'));

    for ($i=0; $i<count($groups->groups); $i++) {
      $group = $groups->groups[$i];
      if ($group->id === $id) {
        array_splice($groups->groups, $i, 1);
        break;
      }
    }

    unlink("../data/$id.json");
    unlink("../icons/$id.png");

    file_put_contents("../data/groups.json", json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  }

  function append_group($group) {
    $groups = json_decode(file_get_contents('../data/groups.json'));
    array_push($groups->groups, $group);
    file_put_contents("../data/groups.json", json_encode($groups, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
  }

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['new_group'])) {
      $group = handle_new_group();
      append_group($group);
    } else
    if (isset($_POST['edit_group'])) {
      $group = handle_update_group();
      update_group($group);
    } else
    if (isset($_POST['remove_group'])) {
      $id = filter_var($_POST['group_id'], FILTER_SANITIZE_STRING);
      delete_group($id);
    }
  }

  $groups = json_decode(file_get_contents('../data/groups.json'))->groups;

  foreach ($groups as $group) {
    $group->removeModalId = "remove_modal_" . $group->id;
    $group->editModalId = "edit_modal_" . $group->id;
    $group->editFormId = "edit_form_" . $group->id;
    $group->removeFormId = "remove_form_" . $group->id;
    $group->downloadHref = "export.php?group_id=" . $group->id;
  }
?>

<!doctype html>
<html class="no-js" lang="">
<head>
  <meta charset="utf-8">
  <title>Управление картой</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <meta property="og:title" content="">
  <meta property="og:type" content="">
  <meta property="og:url" content="">
  <meta property="og:image" content="">

  <link rel="manifest" href="site.webmanifest">
  <link rel="apple-touch-icon" href="icon.png">
  <!-- Place favicon.ico in the root directory -->

  <link rel="stylesheet" href="css/normalize.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
  <link rel="stylesheet" href="css/main.css">

  <meta name="theme-color" content="#fafafa">
</head>

<body>
  <!-- Add your site or application content here -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container">
      <a class="navbar-brand" href="#">Управление картой</a>
    </div>
  </nav>
  <div class="container">
    <div class="groups">
      <ul class="list-group list-group-flush">
        <?php
          foreach ($groups as $group) { ?>
            <li class="list-group-item">
              <div class="group">
                <div class="group-header">
                  <b><?= $group->name ?></b> <span class="badge badge-secondary"><?= $group->count ?></span>
                  <div class="group-header-controls">
                    <a href="<?= $group->downloadHref ?>" download="<?= $group->id ?>.geojson" class="btn btn-light btn-sm">
                      <span>Экспортировать</span>
                      <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-download" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                        <path fill-rule="evenodd" d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                      </svg>
                    </a>
                    <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#<?= $group->editModalId ?>">
                      <span>Редактировать</span>
                      <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-pencil" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5L13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175l-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                      </svg>
                    </button>
                    <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#<?= $group->removeModalId ?>">
                      <span>Удалить</span>
                      <svg width="1em" height="1em" viewBox="0 0 16 16" class="bi bi-trash" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                        <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                        <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                      </svg>
                    </button>
                  </div>
                </div>
              </div>
            </li>
          <?php }
        ?>
        <li class="list-group-item">
          <button class="btn btn-primary" data-toggle="modal" data-target="#new_group_modal">Добавить группу</button>
          <button class="btn btn-secondary" data-toggle="modal" data-target="#import_modal">Импорт</button>
        </li>
      </ul>
    </div>
  </div>

  <?php
    foreach ($groups as $group) { ?>
      <div class="modal" data-backdrop="static" data-keyboard="false" id="<?= $group->removeModalId ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Вы уверены?</h5>
              <button type="button" class="close" data-dismiss="modal">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              Вы уверены, что стоит удалить коллекцию точек <b><?= $group->name ?></b>?
              <form method="post" enctype="multipart/form-data" id="edit_group_<?= $group->removeFormId ?>">
                <input type="hidden" name="remove_group" value="true" />
                <input type="hidden" name="group_id" value="<?= $group->id ?>" />
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-danger" form="edit_group_<?= $group->removeFormId ?>">Да, удалить</button>
            </div>
          </div>
        </div>
      </div>
    <?php }
  ?>

  <?php
    foreach ($groups as $group) { ?>
      <div class="modal" data-backdrop="static" data-keyboard="false" id="<?= $group->editModalId ?>">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Редактировать группу <b><?= $group->name ?></b></h5>
              <button type="button" class="close" data-dismiss="modal">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form method="post" enctype="multipart/form-data" id="edit_group_<?= $group->editFormId ?>">
                <input type="hidden" name="edit_group" value="true" />
                <input type="hidden" name="group_id" value="<?= $group->id ?>" />
                <div class="form-group">
                  <label for="group_name">Название группы</label>
                  <input type="text" class="form-control" name="group_name" value="<?= $group->name ?>" id="group_name" aria-describedby="group_name_help">
                  <small id="group_name_help" class="form-text text-muted">Название группы будет отражено на карте</small>
                </div>
                <div class="form-group">
                  <label for="group_icon">Иконка группы</label>
                  <input type="file" class="form-control-file" name="group_icon" id="group_icon" accept=".png" aria-describedby="group_icon_help">
                  <small id="group_icon_help" class="form-text text-muted">Иконка группы в .png размером 48×48px, будет показана на карте</small>
                </div>
                <div class="form-group">
                  <label for="group_file">Точки группы</label>
                  <input type="file" class="form-control-file" name="group_file" id="group_file" accept=".json, .geojson" aria-describedby="group_file_help">
                  <small id="group_file_help" class="form-text text-muted">Файл <code>.geojson</code>, экспортируемый <a href="https://yandex.ru/map-constructor" target="_blank">Яндекс.Конструктором карт</a></small>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-success" form="edit_group_<?= $group->editFormId ?>">Сохранить</button>
            </div>
          </div>
        </div>
      </div>
    <?php }
  ?>

  <div class="modal" data-backdrop="static" data-keyboard="false" id="new_group_modal">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Новая группа</h5>
          <button type="button" class="close" data-dismiss="modal">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form method="post" enctype="multipart/form-data" id="new_group_form">
            <input type="hidden" name="new_group" value="true" />
            <div class="form-group">
              <label for="group_name">Название группы</label>
              <input type="text" class="form-control" name="group_name" id="group_name" aria-describedby="group_name_help">
              <small id="group_name_help" class="form-text text-muted">Название группы будет отражено на карте</small>
            </div>
            <div class="form-group">
              <label for="group_icon">Иконка группы</label>
              <input type="file" class="form-control-file" name="group_icon" id="group_icon" accept=".png" aria-describedby="group_icon_help">
              <small id="group_icon_help" class="form-text text-muted">Иконка группы в .png размером 48×48px, будет показана на карте</small>
            </div>
            <div class="form-group">
              <label for="group_file">Точки группы</label>
              <input type="file" class="form-control-file" name="group_file" id="group_file" accept=".json, .geojson" aria-describedby="group_file_help">
              <small id="group_file_help" class="form-text text-muted">Файл <code>.geojson</code>, экспортируемый <a href="https://yandex.ru/map-constructor" target="_blank">Яндекс.Конструктором карт</a></small>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
          <button type="submit" class="btn btn-success" form="new_group_form">Добавить</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal" data-backdrop="static" data-keyboard="false" id="import_modal">
    <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Импорт данных</h5>
              <button type="button" class="close" data-dismiss="modal">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <a href="csv_template.php" download="template.csv">Скачать шаблон (.csv)</a>
              <form action="import.php" method="post" enctype="multipart/form-data" id="import_groups_form">
                <div class="form-group">
                  <label for="groups">Новые точки</label>
                  <input type="file" class="form-control-file" name="groups_file" id="groups_file" accept=".csv" aria-describedby="groups_file_help">
                  <small id="groups_file_help" class="form-text text-muted">Файл в формате <code>.csv</code>, заполненный по шаблону</small>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
              <button type="submit" class="btn btn-success" form="import_groups_form">Импортировать</button>
            </div>
          </div>
        </div>
      </div>
  </div>

  <script src="js/vendor/modernizr-3.11.2.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js" integrity="sha384-9/reFTGAW83EW2RDu2S0VKaIzap3H66lZH81PoYlFhbGU+6BZp6G7niu735Sk7lN" crossorigin="anonymous"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.min.js" integrity="sha384-w1Q4orYjBQndcko6MimVbzY0tgp4pWB4lZ7lr30WKz0vr/aWKhXdBNmNb5D92v7s" crossorigin="anonymous"></script>
  <script src="js/plugins.js"></script>
  <script src="js/main.js"></script>

  <!-- Google Analytics: change UA-XXXXX-Y to be your site's ID. -->
  <script>
    window.ga = function () { ga.q.push(arguments) }; ga.q = []; ga.l = +new Date;
    ga('create', 'UA-XXXXX-Y', 'auto'); ga('set', 'anonymizeIp', true); ga('set', 'transport', 'beacon'); ga('send', 'pageview')
  </script>
  <script src="https://www.google-analytics.com/analytics.js" async></script>
</body>

</html>
