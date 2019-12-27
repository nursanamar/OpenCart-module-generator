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
        $this->makeModel($path,$filename);
        $this->makeTwig($path,$filename);

        $language_file = $this->makeLanguage($path,$filename);
        
        $this->taskWriteToFile($language_file)
            ->line("<?php\n")
            ->line('$_["heading_title"] = "'.$name.'";')
            ->run();
    }

    private function makeController($path, $filename)
    {
        $pathPart = explode("/", $path);
       
        $class_name = "Controller".str_replace(" ","",ucwords(implode(" ",$pathPart)));
        $class_name .= str_replace(" ", '', ucwords(str_replace('_', ' ', $filename)));
        
        $dir = getenv('MODULE_FOLDER')."/upload/admin/controller/" . $path . "/";
        $file_path = $dir.$filename.".php";

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
        
        $dir = getenv('MODULE_FOLDER')."/upload/catalog/controller/" . $path . "/";
        $file_path = $dir.$filename.".php";

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
       
        $class_name = "Model".str_replace(" ","",ucwords(implode(" ",$pathPart)));
        $class_name .= str_replace(" ", '', ucwords(str_replace('_', ' ', $filename)));
        
        $dir = getenv('MODULE_FOLDER')."/upload/admin/model/" . $path . "/";
        $file_path = $dir.$filename.".php";

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
        $dir = getenv('MODULE_FOLDER')."/upload/admin/view/template/" . $path . "/";
        $file_path = $dir.$filename.".twig";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        $dir = getenv('MODULE_FOLDER')."/upload/catalog/view/template/" . $path . "/";
        $file_path = $dir.$filename.".twig";

        $this->_mkdir($dir);
        $this->_touch($file_path);
    }

    private function makeLanguage($path,$filename,$code = 'en-gb'){

        $dir = getenv('MODULE_FOLDER').'/upload/admin/language/'.$code.'/'.$path.'/';
        $file_path = $dir.$filename.".php";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        return $file_path;
    }

    public function moduleInstall()
    {
        $this->taskFilesystemStack()
            ->mirror(getenv('MODULE_FOLDER').'/upload', getenv('OC_ROOT'))
            ->copy(getenv('MODULE_FOLDER').'/install.xml', getenv('OC_ROOT') . '/system/install.ocmod.xml')
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
            "no-obfuscate-variable-name"
        ];

        $obfuscator = new Obfuscator([
            "allowed_mime_types" => ['text/x-php'],
            "obfuscation_options" => $options,
        ]);

        $obfuscator->obfuscateDirectory(getenv("MODULE_FOLDER").'/', 'obf/');

        $this->taskFilesystemStack()->copy(getenv("MODULE_FOLDER").'/install.xml','obf/install.xml')->run();

        $module_name = getenv("MODULE_NAME") ? getenv("MODULE_NAME") : "build";
        $ver = getenv("MODULE_VER") ? "v" . getenv("MODULE_VER") : '';
        $filename = $module_name . $ver . '-obf.ocmod.zip';
        $this->taskExec('zip')->dir('obf')->arg('-r')->arg('../build/' . $filename)->arg('./')->run();

        $this->taskDeleteDir('obf')->run();
    }
}
