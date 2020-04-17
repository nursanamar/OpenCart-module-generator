<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */

require_once 'vendor/autoload.php';

if (file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::create(__DIR__);
    $dotenv->load();
}

use pmaslak\PhpObfuscator\Obfuscator;

class RoboFile extends \Robo\Tasks
{
    public function moduleNew()
    {
        $name = $this->ask("Module Name");

        $filename = str_replace(' ', '_', strtolower($name));
        $path = "extension/module";

        $this->makeController($path, $filename);
        $this->makeModel($path, $filename);
        $this->makeTwig($path, $filename);

        $language_file = $this->makeLanguage($path, $filename);

        $this->taskWriteToFile($language_file)
            ->line("<?php\n")
            ->line('$_["heading_title"] = "' . $name . '";')
            ->run();
    }

    private function makeController($path, $filename)
    {
        $pathPart = explode("/", $path);

        $class_name = "Controller" . str_replace(" ", "", ucwords(implode(" ", $pathPart)));
        $class_name .= str_replace(" ", '', ucwords(str_replace('_', ' ', $filename)));

        $dir = getenv('MODULE_FOLDER') . "/upload/admin/controller/" . $path . "/";
        $file_path = $dir . $filename . ".php";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        $this->say($class_name);
        $this->taskWriteToFile($file_path)
            ->line("<?php\n")
            ->line("class " . $class_name . " extends Controller {\n")
            ->line("\tpublic function index(){\n")
            ->line("\t}")
            ->line("}\n")
            ->run();

        $dir = getenv('MODULE_FOLDER') . "/upload/catalog/controller/" . $path . "/";
        $file_path = $dir . $filename . ".php";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        $this->say($class_name);
        $this->taskWriteToFile($file_path)
            ->line("<?php\n")
            ->line("class " . $class_name . " extends Controller {\n")
            ->line("\tpublic function index(){\n")
            ->line("\t}")
            ->line("}\n")
            ->run();
    }

    private function makeModel($path, $filename)
    {
        $pathPart = explode("/", $path);

        $class_name = "Model" . str_replace(" ", "", ucwords(implode(" ", $pathPart)));
        $class_name .= str_replace(" ", '', ucwords(str_replace('_', ' ', $filename)));

        $dir = getenv('MODULE_FOLDER') . "/upload/admin/model/" . $path . "/";
        $file_path = $dir . $filename . ".php";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        $this->say($class_name);
        $this->taskWriteToFile($file_path)
            ->line("<?php\n")
            ->line("class " . $class_name . " extends Model {\n\n")
            ->line("}\n")
            ->run();
    }

    private function makeTwig($path, $filename)
    {
        $dir = getenv('MODULE_FOLDER') . "/upload/admin/view/template/" . $path . "/";
        $file_path = $dir . $filename . ".twig";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        $dir = getenv('MODULE_FOLDER') . "/upload/catalog/view/theme/default/template/" . $path . "/";
        $file_path = $dir . $filename . ".twig";

        $this->_mkdir($dir);
        $this->_touch($file_path);
    }

    private function makeLanguage($path, $filename, $code = 'en-gb')
    {

        $dir = getenv('MODULE_FOLDER') . '/upload/admin/language/' . $code . '/' . $path . '/';
        $file_path = $dir . $filename . ".php";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        return $file_path;
    }

    public function moduleInstall()
    {
        $this->taskFilesystemStack()
            ->mirror(getenv('MODULE_FOLDER') . '/upload', getenv('OC_ROOT'))
            ->copy(getenv('MODULE_FOLDER') . '/install.xml', getenv('OC_ROOT') . '/system/install.ocmod.xml')
            ->run();
    }

    public function moduleWatch()
    {
        $this->moduleInstall();

        $this->taskWatch()->monitor(getenv("MODULE_FOLDER"), function () {
            $this->moduleInstall();
        })->run();
    }

    public function moduleBuild($opts = ["with-obf" => false])
    {
        $this->taskDeleteDir('build')->run();
        $this->taskFileSystemStack()->mkdir('build')->run();

        $module_name = getenv("MODULE_NAME") ? getenv("MODULE_NAME") : "build";
        $ver = getenv("MODULE_VER") ? "v" . getenv("MODULE_VER") : '';
        $filename = $module_name . $ver . '.ocmod.zip';
        $this->taskExec('zip')->dir(getenv("MODULE_FOLDER"))->arg('-r')->arg('../build/' . $filename)->arg('./')->run();

        if ($opts['with-obf']) {
            $this->moduleObf();
        }
    }

    public function moduleObf()
    {
        $this->taskDeleteDir('obf')->run();
        $this->taskFileSystemStack()->mkdir('obf')->run();

        $options = [
            "no-obfuscate-method-name",
            "no-obfuscate-class-name",
            "no-obfuscate-property-name",
            "no-obfuscate-constant-name",
            "no-obfuscate-variable-name",
        ];

        $obfuscator = new Obfuscator([
            "allowed_mime_types" => ['text/x-php'],
            "obfuscation_options" => $options,
        ]);

        $obfuscator->obfuscateDirectory(getenv("MODULE_FOLDER") . '/', 'obf/');

        $this->taskFilesystemStack()->copy(getenv("MODULE_FOLDER") . '/install.xml', 'obf/install.xml')->run();

        $module_name = getenv("MODULE_NAME") ? getenv("MODULE_NAME") : "build";
        $ver = getenv("MODULE_VER") ? "v" . getenv("MODULE_VER") : '';
        $filename = $module_name . $ver . '-obf.ocmod.zip';
        $this->taskExec('zip')->dir('obf')->arg('-r')->arg('../build/' . $filename)->arg('./')->run();

        $this->taskDeleteDir('obf')->run();
    }

    public function moduleDeploy()
    {
        $storeDomain = getenv("STORE_DOMAIN");
        $account = [
            "username" => getenv("STORE_USERNAME"),
            "password" => getenv("STORE_PASSWORD"),
        ];

        $this->io()->title("Start deploying module to " . $storeDomain);

        $this->io()->section("Build the module");
        $this->moduleBuild();

        $this->io()->section("Connect to " . $storeDomain);
        $store = new Store($storeDomain, $account);

        $module_name = getenv("MODULE_NAME") ? getenv("MODULE_NAME") : "build";
        $ver = getenv("MODULE_VER") ? "v" . getenv("MODULE_VER") : '';
        $filename = $module_name . $ver . '.ocmod.zip';

        $this->io()->section("Upload " . $filename);
        $store->uploadModule("build/" . $filename);

        $this->io()->section("Refresh store modification");
        $store->refreshModification();

        $this->io()->success("Done, module installed on " . $storeDomain);

    }
}

class Store
{

    private $ch;
    private $domain;
    private $adminAccount;
    private $user_token = null;
    private $path = [
        "login" => "common/login",
        "modification_refresh" => "marketplace/modification/refresh",
        "upload_module" => "marketplace/installer/upload",
    ];

    public function __construct($domain, $adminAccount, $routes = [])
    {

        $this->domain = $domain;
        $this->adminAccount = $adminAccount;
        $this->path = array_merge($this->path, $routes);

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_COOKIEJAR => dirname(__FILE__) . '/cookie.txt',
            CURLOPT_COOKIEFILE => dirname(__FILE__) . '/cookie.txt',
            CURLOPT_VERBOSE => true,
        ));

        $verbose = fopen(dirname(__FILE__) . '/errorlog.txt', 'w');
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $this->ch = $ch;

        $this->login();
    }

    public function login()
    {
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $this->domain . "admin/index.php?route=" . $this->path['login'],
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => http_build_query($this->adminAccount),
        ]);

        $response = curl_exec($this->ch);
        $url_info = curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);

        $url = parse_url($url_info, PHP_URL_QUERY);
        parse_str($url, $query);

        if (isset($query['user_token'])) {
            $this->user_token = $query['user_token'];
        }
    }

    public function refreshModification()
    {
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $this->domain . "admin/index.php?route=" . $this->path['modification_refresh'] . "&user_token=" . $this->user_token,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $response = curl_exec($this->ch);
        $url_info = curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL);

        $url = parse_url($url_info, PHP_URL_QUERY);
        parse_str($url, $query);

    }

    public function uploadModule($modulePath)
    {
        $file = [
            'file' => new CurlFile($modulePath, 'application/zip', basename($modulePath)),
        ];

        curl_setopt_array($this->ch, [
            CURLOPT_URL => $this->domain . "admin/index.php?route=" . $this->path['upload_module'] . "&user_token=" . $this->user_token,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $file,
        ]);

        $response = curl_exec($this->ch);

        $response = json_decode($response, true);

        print($response['text'] . "\n");
        $this->next($response['next']);
    }

    public function next($url)
    {
        curl_setopt_array($this->ch, [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => "GET",
        ]);

        $response = curl_exec($this->ch);

        $response = json_decode($response, true);

        if (isset($response['text'])) {
            print($response['text'] . "\n");
        }

        if (isset($response['next'])) {
            $this->next($response['next']);
        }
    }

    public function __destruct()
    {
        curl_close($this->ch);
        unlink(dirname(__FILE__) . '/cookie.txt');
        unlink(dirname(__FILE__) . '/errorlog.txt');
    }

}
