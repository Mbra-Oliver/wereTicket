<?php

namespace App\Http\Controllers;

use App\Models\Event\Ticket;
use Artisan;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class UpdateController extends Controller
{
  public function recurse_copy($src, $dst)
  {
    $srcPath = base_path($src);
    $dstPath = base_path($dst);

    // If source is not a directory, copy the file directly
    if (!is_dir($srcPath)) {
      @mkdir(dirname($dstPath), 0775, true);
      return copy($srcPath, $dstPath);
    }

    $dir = opendir($srcPath);
    @mkdir($dstPath, 0775, true);

    while (false !== ($file = readdir($dir))) {
      if (($file != '.') && ($file != '..')) {
        $currentSrc = $srcPath . DIRECTORY_SEPARATOR . $file;
        $currentDst = $dstPath . DIRECTORY_SEPARATOR . $file;

        if (is_dir($currentSrc)) {
          $this->recurse_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
        } else {
          copy($currentSrc, $currentDst);
        }
      }
    }
    closedir($dir);
  }

  public function upversion()
  {
    $assets = array(
      ['path' => 'public/assets/admin/css', 'type' => 'folder', 'action' => 'replace'],
      ['path' => 'public/assets/admin/js', 'type' => 'folder', 'action' => 'replace'],

      ['path' => 'public/assets/front/css', 'type' => 'folder', 'action' => 'replace'],
      ['path' => 'public/assets/front/js', 'type' => 'folder', 'action' => 'replace'],
      ['path' => 'public/.htaccess', 'type' => 'file', 'action' => 'replace'],
      ['path' => 'public/config.php', 'type' => 'file', 'action' => 'replace'],
      ['path' => 'public/sw.js', 'type' => 'file', 'action' => 'replace'],

      ['path' => 'public/pgw', 'type' => 'folder', 'action' => 'add'],

      ['path' => 'app', 'type' => 'folder', 'action' => 'replace'],
      ['path' => 'config', 'type' => 'folder', 'action' => 'replace'],
      ['path' => 'resources/views', 'type' => 'folder', 'action' => 'replace'],
      ['path' => 'database/migrations', 'type' => 'folder', 'action' => 'add'],

      ['path' => 'routes/admin.php', 'type' => 'file', 'action' => 'replace'],
      ['path' => 'routes/organizer.php', 'type' => 'file', 'action' => 'replace'],
      ['path' => 'routes/web.php', 'type' => 'file', 'action' => 'replace'],

      ['path' => 'version.json', 'type' => 'file', 'action' => 'replace'],
      ['path' => 'composer.json', 'type' => 'file', 'action' => 'replace'],
      ['path' => 'composer.lock', 'type' => 'file', 'action' => 'replace']
    );

    foreach ($assets as $key => $asset) {
      // if updater need to replace files / folder (with/without content)
      if ($asset['action'] == 'replace') {
        if ($asset['type'] == 'file') {
          copy(base_path('public/updater/' . $asset["path"]), base_path($asset["path"]));
        }
        if ($asset['type'] == 'folder') {
          $this->delete_directory($asset["path"]);
          $this->recurse_copy('public/updater/' . $asset["path"], $asset["path"]);
        }
      }
      // if updater need to add files / folder (with/without content)
      elseif ($asset['action'] == 'add') {
        if ($asset['type'] == 'folder') {
          @mkdir(base_path($asset["path"]), 0775, true);
          $this->recurse_copy('public/updater/' . $asset["path"], $asset["path"]);
        }
      }
    }

    $this->updateLanguage();
    $this->updateAdminLanguage();
    Artisan::call('config:clear');
    Artisan::call('migrate');

    $this->includeSlotSystemVariable();
    Artisan::call('config:clear');
    Session::flash('success', 'Updated successfully');
    return redirect('updater/success.php');
  }

  function delete_directory($dirname)
  {
    $dir_handle = null;
    if (is_dir($dirname))
      $dir_handle = opendir($dirname);

    if (!$dir_handle)
      return false;
    while ($file = readdir($dir_handle)) {
      if ($file != "." && $file != "..") {
        if (!is_dir($dirname . "/" . $file))
          unlink($dirname . "/" . $file);
        else
          $this->delete_directory($dirname . '/' . $file);
      }
    }
    closedir($dir_handle);
    rmdir($dirname);
    return true;
  }


  public function redirectToWebsite(Request $request)
  {
    $arr = ['WEBSITE_HOST' => $request->website_host];
    setEnvironmentValue($arr);
    Artisan::call('config:clear');

    return redirect()->route('index');
  }

  public function updateLanguage()
  {
    $langCodes = [];
    $languages = Language::all();
    foreach ($languages as $key => $language) {
      $langCodes[] = $language->code;
    }
    $langCodes[] = 'default';

    foreach ($langCodes as $key => $langCode) {
      // read website language json file
      $data = file_get_contents(base_path('resources/lang/') . $langCode . '.json');

      // decode website default json
      $json_arr = json_decode($data, true);

      // insert new keywords
      $newKeywordsJson = file_get_contents(base_path('public/updater/language.json'));
      $newKeywords = json_decode($newKeywordsJson, true);
      foreach ($newKeywords as $key => $newKeyword) {
        if (!array_key_exists($key, $json_arr)) {
          $json_arr[$key] = $key;
        }
      }
      // push the new key-value pairs in language json files
      file_put_contents(base_path('resources/lang/') . $langCode . '.json', json_encode($json_arr));
    }
  }

  public function updateAdminLanguage()
  {
    $langCodes = ['admin'];

    foreach ($langCodes as $key => $langCode) {
      // read website language json file
      $data = file_get_contents(base_path('resources/lang/') . $langCode . '.json');

      // decode website default json
      $json_arr = json_decode($data, true);

      // insert new keywords
      $newKeywordsJson = file_get_contents(base_path('public/updater/admin_language.json'));
      $newKeywords = json_decode($newKeywordsJson, true);
      foreach ($newKeywords as $key => $newKeyword) {
        if (!array_key_exists($key, $json_arr)) {
          $json_arr[$key] = $key;
        }
      }
      // push the new key-value pairs in language json files
      file_put_contents(base_path('resources/lang/') . $langCode . '.json', json_encode($json_arr));
    }
  }

  public function includeSlotSystemVariable()
  {

    $tickets = Ticket::where('event_type', 'venue')->whereNotNull('variations')->get();
    foreach($tickets as $ticket){
      if (!is_null($ticket->variations)) {
        $variations = json_decode($ticket->variations, true);
        foreach ($variations as &$vari) {
          if (!array_key_exists('slot_enable', $vari)) {
            $vari['slot_enable'] = 0;
          }
          if (!array_key_exists('slot_unique_id', $vari)) {
            $vari['slot_unique_id'] = rand(000000, 999999);
          }
          if (!array_key_exists('slot_seat_min_price', $vari)) {
            $vari['slot_seat_min_price'] = 0.00;
          }
        }
        unset($vari);
        $ticket->update([
          'variations' => json_encode($variations)
        ]);
      }
      if (is_null($ticket->normal_ticket_slot_unique_id)) {
        $ticket->update([
          'normal_ticket_slot_unique_id' => rand(000000, 999999),
        ]);
      }

      if (is_null($ticket->normal_ticket_slot_enable)) {
        $ticket->update([
          'normal_ticket_slot_enable' => 0,
        ]);
      }

      if (is_null($ticket->free_tickete_slot_unique_id)) {
        $ticket->update([
          'free_tickete_slot_unique_id' => rand(000000, 999999),
        ]);
      }

      if (is_null($ticket->free_tickete_slot_enable)) {
        $ticket->update([
          'free_tickete_slot_enable' => 0,
        ]);
      }

      if (is_null($ticket->slot_seat_min_price)) {
        $ticket->update([
          'slot_seat_min_price' => 0.00
        ]);
      }
    }
    return true;
  }

}
