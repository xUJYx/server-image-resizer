<?php
if(!empty($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], Array('94.244.17.6')))
{
    ini_set('memory_limit','-1');
    ini_set('display_errors','on');
    ini_set('display_startup_errors','on');
    ini_set('max_execution_time', '0');
    ini_set('set_time_limit', '0');
    error_reporting(E_ALL);
}

require_once ('vendor/autoload.php');


function find_files($seed)
{
    if(!is_dir($seed)) return false;
    $dirs = array($seed);
    while(NULL !== ($dir = array_pop($dirs)))
    {
        if($dh = opendir($dir))
        {
            while( false !== ($file = readdir($dh)))
            {
                if($file == '.' || $file == '..') continue;
                $path = $dir . '/' . $file;
                if (preg_match('/^.*\.(jpe?g|png)$/i', $path))
                {
                    $rand_file_name = getRandFileName($dir, $file); // получаем случайное имя картинки
                    createCSV($dir, $file, $rand_file_name);
                }
                if(is_dir($path)) { $dirs[] = $path; }

            }
        closedir($dh);
        }
    }

    return $message = "CSV файл успешно создан \r\n Для продолжения нажмите <span style=\"font-weight:bold; color:red;\">SHIFT+F5</span>\r\n";
}

function copyImgForResize($images_path, $dir_to_resize, $renamed_images_name_list)
{
    if (!is_dir('./'.$dir_to_resize.'/')) mkdir('./'.$dir_to_resize.'/');
    $new_images_path = './' . $dir_to_resize . '/';
    for ($i=0; $i<count($images_path); $i++)
    {
        if(is_file($images_path[$i]) && filesize($images_path[$i]) != 0 && !is_file($new_images_path.$renamed_images_name_list[$i]))
        {
            if(!copy($images_path[$i], $new_images_path . $renamed_images_name_list[$i]))
            {
                echo "Не удалось скопировать {$images_path[$i]}\r\n";
            } elseif(copy($images_path[$i], $new_images_path . $renamed_images_name_list[$i])) {
                echo "Успешно скопирован файл [$i] {$images_path[$i]} в => {$new_images_path}{$renamed_images_name_list[$i]} \r\n";
            } else createCSV($images_path[$i], $new_images_path.$renamed_images_name_list[$i], $i, "erorr_file.csv");
        }
    }
    return "\r\n<span style=\"font-weight:bold; color:red;\">Все изображения успешно скопированы</span> \r\n";
}

// Функция, которая возвращает рандомное имя картинки на основании её пути
function getRandFileName($dir, $file)
{
    $rand_file_name = '';
    $rand_dir_arr = explode("/", $dir);
    for ($i=0;$i<count($rand_dir_arr); $i++)
    {
        if (strlen($rand_dir_arr[$i])>2) //проверяем длинну названия папки
        {
            $rand_file_name .= $rand_dir_arr[$i][0];
        }
    }
    $rand_file_name .= rand(10,99999) . '__' . $file;
    return $rand_file_name;
}

// Функция, которая создаёт CSV фал
function createCSV ($path, $image_name, $rand_file_name, $filename = 'list_of_images.csv')
{
    $path = str_replace("./", "/", $path);
    $CSVdata = "$image_name;$path;$rand_file_name;\r\n";
    file_put_contents($filename, $CSVdata, FILE_APPEND);
}
// END Function which creates an CSV file

function readCSV ($filename = 'list_of_images.csv')
{
    $csv_content = file($filename);
    foreach ($csv_content as $key => $content)
    {
        $csv[] = explode(";", $content);
        if(isset($csv[$key][3])) unset($csv[$key][3]);
    }
    return $csv;
}
function writeCSV ($filename, $data, $rewrite = 0)
{
    // В $data должен приходить массив!!!
    // Максимально принимаются двумерные массивы!!!

    $filename = "./" . $filename;
    $CSVdata = '';
    $error = '';
    if($rewrite == "rewrite") unlink($filename);

    foreach ($data as $value)
    {
        if (is_array($value)) $CSVdata = implode(";", $value);
        else $CSVdata = $value;
        $CSVdata .= ";\r\n";
        if (!file_put_contents($filename, $CSVdata, FILE_APPEND)) $error = "error";
    }
        if(!$error) return true;
}

function getWorkData ($resized_directory = "testing", $dir_to_search = "img", $dir_to_resize = "resized")
{
    if (!file_exists('list_of_images.csv')) {
        if ($dir_to_search == '.') $message = find_files('.'); // если пришла точка - ищем по всем файлам срвера
        else $message = find_files("./" . $dir_to_search); // иначе ищем в указанной папке
        if ($message) {
            echo "<p>" . $message . "</p>\r\n";
        }
        return;
    }

    $csv_data = readCSV();

    // creating array for catalog creation
    $images_directories[] = '';
    foreach ($csv_data as $content) {
        $images_directories[] = $content[1];
        $images_path[] = "." . $content[1] . "/" . $content[0];
        $images_to_resize_path[] = "./" . $dir_to_resize . $content[1] . "/" . $content[2];
        $not_resized_images_name_list[] = $content[0];
        $renamed_images_name_list[] = $content[2];
    }
    $images_directories_list = $images_directories;
    unset($images_directories_list['0']); // удаляем пустой элемент массива
    $images_directories = array_unique($images_directories);
    // END creating array for catalog creation

//    echo copyImgForResize($images_path, $dir_to_resize, $renamed_images_name_list); // Копируем картинки в папку для последующего ресайза


//die();


   /* if(!isset($_GET['upload']))*/ echo "Для копирования файлов из директории {$dir_to_resize} в директорию $resized_directory <a href=\"http://lvivskiy-maetok.com.ua/img-resizer.php?upload\"><span style=\"font-weight:bold; color:red;\">ПЕРЕЙДИТЕ ПО ССЫЛКЕ</span></a> \r\n";

//    if(isset($_GET['upload']))
//        {
            $resized_images_list = resizedImagesList($dir_to_resize); // дальше ищем эллементы этого списка в $imagess_path при помощи array_intersect($imagess_path, $resized_images_list). При совпадении в ключах будем иметь значения совпавших ключей - по ключам копируем эту картинку по новому пути.

    // Если во время пережима произошла ошибка и в папке с пережатыми картинками содержатся файлы "resized_"
    /*foreach($resized_images_list as $key => $value)
        {
            $resized_images_list[$key] = str_replace("resized_", '', $resized_images_list[$key]);
        }*/
    // END Если во время пережима произошла ошибка и в папке с пережатыми картинками содержатся файлы "resized_"
            $renamed_list_to_copy = array_intersect($renamed_images_name_list, $resized_images_list);

        $work_done = false;
        while ($work_done === false)
        {
            try {
                if($work_done = tinifyImg($renamed_list_to_copy, $dir_to_resize)) echo "\r\n Все картинки пережаты \r\n";
                else tinifyImg($renamed_list_to_copy, $dir_to_resize);
            } catch (Exception $e) {
                if($work_done = tinifyImg($renamed_list_to_copy, $dir_to_resize)) echo "\r\n Все картинки пережаты \r\n";
                else tinifyImg($renamed_list_to_copy, $dir_to_resize);
            }
        }


        if (createDirectories($images_directories, $resized_directory)) echo "<span style=\"font-weight:bold; color:red;\">Все директории успешно созданы</span> \r\n";

//            echo "\r\n SPARTAAA!!!11111111 \r\n";
//            var_dump($renamed_list_to_copy);
//            die("\r\n SPARTA!!!22222222222222");


            foreach ($renamed_list_to_copy as $key => $data) {
                copyImgWithStructure($images_path[$key], $renamed_images_name_list[$key], $dir_to_resize, $resized_directory);
            }
            rmdir($dir_to_resize);
            echo "<span style=\"font-weight:bold; color:red;\">Работа завершена</span>\r\n";
 //       }
}

function resizedImagesList ($resized)
{
    $resized = "./".$resized;
    $resized_images_list = array();
    if(!is_dir("./" . $resized)) return false;
    $directories = array($resized);
    while(NULL !== ($dir = array_pop($directories)))
    {
        if($dh = opendir($dir))
        {
            while( false !== ($file = readdir($dh)))
            {
                if($file == '.' || $file == '..') continue;
                $path = $dir . '/' . $file;
                if (preg_match('/^.*\.(jpe?g|png)$/i', $path))
                {
                    $resized_images_list[] = $file;
                }
            }
            closedir($dh);
        }
    }

    if (!$resized_images_list) return false;
    return $resized_images_list;
}

function createDirectories($images_directories, $resized_dir)
{
    $resized_dir = './' . $resized_dir;
    foreach ($images_directories as $directory)
    if (!is_dir($directory))
    {
        $piece_of_dir = explode("/", $directory);
        $piece_of_dir_resized = $resized_dir;
        foreach ($piece_of_dir as $key => $value)
        {
            $piece_of_dir_resized .= $value . "/";
            if(!is_dir($piece_of_dir_resized) && !mkdir($piece_of_dir_resized)) echo "НЕ МОГУ СОЗДАТЬ ПАПКУ \"$piece_of_dir_resized\" !!!\r\n";
        }
    }
    return true;
}

function copyImgWithStructure ($images_path, $renamed_images_name_list, $dir_to_resize, $resized_directory)
{
    static $file_copy_counter = 0;
    echo "\r\n $file_copy_counter \r\n";
    $images_resized_path = "./" . $dir_to_resize . "/" . "resized_" . $renamed_images_name_list;
    $images_new_path = str_replace("./", "./". $resized_directory ."/", $images_path);
//    var_dump($images_new_path); die();
    if(rename($images_resized_path, $images_new_path))
    {
        echo "Успешно скопирован файл [{$file_copy_counter}] из $images_resized_path в => $images_new_path \r\n";
        createCSV ($file_copy_counter, $images_resized_path, $images_new_path, 'work-done-list.csv');
    }elseif(!rename($images_resized_path, $images_new_path))
    {
        echo "Ошибка копирования файла [{$file_copy_counter}] из $images_resized_path в => $images_new_path \r\n";
    }
    $file_copy_counter++;
}

function tinifyImg ($renamed_list_to_copy, $dir_to_resize)
{
    static $file_counter = 1;
    foreach ($renamed_list_to_copy as $image_name)
    {
        $api_key = getApiKey();

        $resized_file_path = './'. $dir_to_resize . '/resized_' . $image_name;
        $image_path = './' . $dir_to_resize . '/' . $image_name;
        if (filesize($image_path) == 0)
        {
            unlink($image_path);
            continue;
        }
        echo "\r\nИспользую API ключ {$api_key[0]}, ещё возможно использований: {$api_key[1]} \r\n";
        echo "Пережимаю файл [" . $file_counter . "] из [" . count($renamed_list_to_copy) . "] $image_path \r\n";
        \Tinify\setKey($api_key[0]);
        $resized_img = \Tinify\fromFile($image_path)->preserve("copyright", "creation", "location")->toFile($resized_file_path);

        if($resized_img == true)
        {
            echo "Файл $image_path успешно пережат! \r\n";
        } else
        {
            echo "Файл $image_path НЕ ПЕРЕЖАТ! \r\n";
        }

        if($compressionsThisMonth = \Tinify\compressionCount())
        {
            $api_key[1] = 500 - $compressionsThisMonth;
            echo "Осталось активных [" . $api_key[1] . "] пережатий картинок в этом месяце \r\n";
        }
        else echo "Проверить остаток проверок на tinify не удалось.";
        unlink($image_path);
        $file_counter++;
        writeNewApiKey($api_key);
    }
    return true;
}

function writeNewApiKey($api_key)
{
    $api_list = getApiKey('list');
    foreach ($api_list as $key => $value)
    {
        if (array_search($api_key[0], $value) === (int)'0') $api_list[$key][1] = $api_key[1];
    }

    writeCSV("api-tiny-list.csv", $api_list, "rewrite");
}

function getApiKey($list = 0)
{
    $api_counter = 0;
    $api_key_list = readCSV('api-tiny-list.csv');
    shuffle($api_key_list);

    foreach ($api_key_list as $key => $value)
    {
        $api_key_list[$key] = str_replace(array("\r", "\n", " "), "", $value);
        if(count($api_key_list[$key]) === 1) unset($api_key_list[$key]);
        if (!isset($api_key_list[$key])) continue;

        for ($i=0; $i<count($api_key_list[$key]);$i++)
        {
            if ($api_key_list[$key][$i] === "") unset($api_key_list[$key][$i]);
        }
    }

    if ($list === 'list') return $api_key_list;

    foreach ($api_key_list as $key => $value)
    {
        if ($api_key_list[$key][1] != 0)
        {
         return $api_key_list[$key];
        }
    }
    echo "THERE`S NO AVAILABLE APIs";
    return;
}

getWorkData("images-resize-completed", "img", "images-for-resize"); // Папка куда ложим файлы, папка где ищем картинки, папка откуда подтягиваем обработанные картинки