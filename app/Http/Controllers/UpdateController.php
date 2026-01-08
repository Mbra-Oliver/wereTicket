<?php

namespace App\Http\Controllers;

use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;

class UpdateController extends Controller
{
    public function version()
    {
        return view('updater.version');
    }

    public function recurse_copy($src, $dst)
    {

        $dir = opendir(base_path($src));
        @mkdir(base_path($dst));
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir(base_path($src) . '/' . $file)) {
                    $this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy(base_path($src) . '/' . $file, base_path($dst) . '/' . $file);
                }
            }
        }
        closedir($dir);
    }

    public function upversion(Request $request)
    {

        $assets = array(
            ['path' => 'app', 'type' => 'folder', 'action' => 'replace'],
            ['path' => 'config', 'type' => 'folder', 'action' => 'replace'],
            ['path' => 'resources/views', 'type' => 'folder', 'action' => 'replace'],
            ['path' => 'database/migrations', 'type' => 'folder', 'action' => 'replace'],
            ['path' => 'routes', 'type' => 'folder', 'action' => 'replace'],
            ['path' => 'public/assets/front/css/style.css', 'type' => 'file', 'action' => 'replace'],
            ['path' => 'version.json', 'type' => 'file', 'action' => 'replace']
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
                    $this->recurse_copy('public/updater/' . $asset["path"], $asset["path"]);
                }
            }
        }


        $langs = Language::all();
        $newKeys = [
            "Please wait we will send you a mail with an invoice" => "Please wait we will send you a mail with an invoice",
        ];
        // added keyword for all language
        foreach ($langs as $language) {

            $jsonData = file_get_contents(resource_path('lang/') . $language->code . '.json');
            $keywords = json_decode($jsonData, true);
            $datas = array_merge($newKeys, $keywords);
            $jsonData = json_encode($datas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            $fileLocated = resource_path('lang/') . $language->code . '.json';
            file_put_contents($fileLocated, $jsonData);
        }
        // added keyword for default json
        $defaultjsonData = file_get_contents(resource_path('lang/') . 'default.json');
        $defaultkeywords = json_decode($defaultjsonData, true);
        $defaultdatas = array_merge($newKeys, $defaultkeywords);
        $defaultjsonData = json_encode($defaultdatas, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $fileLocated = resource_path('lang/') . 'default.json';
        file_put_contents($fileLocated, $defaultjsonData);

        $this->updateEnvFile('QUEUE_CONNECTION', 'database');

        $path = public_path('assets/admin/qrcodes'); // Path to the folder

        if (File::exists($path)) {
            File::deleteDirectory($path); 
        }

        Artisan::call('config:clear');
        // run migration files
        Artisan::call('migrate');


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

        return redirect()->route('front.index');
    }

    private function updateEnvFile($key, $value)
    {
        $path = base_path('.env');

        if (file_exists($path)) {
            // Read .env file content
            $envContent = file_get_contents($path);

            // Create pattern and replacement
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            // Update the file content
            if (preg_match($pattern, $envContent)) {
                // Key exists, replace it
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                // Key does not exist, append it
                $envContent .= PHP_EOL . "{$key}={$value}" . PHP_EOL;
            }

            // Write updated content back to .env file
            file_put_contents($path, $envContent);
        }
    }
}
