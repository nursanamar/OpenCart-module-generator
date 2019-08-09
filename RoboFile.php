<?php
/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
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
        
        $dir = "src/upload/admin/controller/" . $path . "/";
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
        
        $dir = "src/upload/catalog/controller/" . $path . "/";
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
        
        $dir = "src/upload/admin/model/" . $path . "/";
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
        $dir = "src/upload/admin/view/template/" . $path . "/";
        $file_path = $dir.$filename.".twig";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        $dir = "src/upload/catalog/view/template/" . $path . "/";
        $file_path = $dir.$filename.".twig";

        $this->_mkdir($dir);
        $this->_touch($file_path);
    }

    private function makeLanguage($path,$filename,$code = 'en-gb'){

        $dir = 'src/upload/admin/language/'.$code.'/'.$path.'/';
        $file_path = $dir.$filename.".php";

        $this->_mkdir($dir);
        $this->_touch($file_path);

        return $file_path;
    }
}
