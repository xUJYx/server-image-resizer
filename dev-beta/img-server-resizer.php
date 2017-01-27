<?php
error_reporting(0);
require_once ('vendor/autoload.php');

/* Функция ищет файлы картинок и отправляет их в функцию для создания CSV */
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

    return $message = "CSV файл успешно создан! \r\n";
}
/* END Функция ищет файлы картинок и отправляет их в функцию для создания CSV */

/* Функция ищет файлы картинок в "папке для пережатия" и сортирует по размеру файла */
function resizedImagesList ($dir_to_resize)
{
    $dir_to_resize = "./".$dir_to_resize;
    $resized_images_list = array();
    $resized_file_size = array();
    if(!is_dir("./" . $dir_to_resize)) return false;
    $directories = array($dir_to_resize);
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
//                    $resized_images_list[] = $file;
                    $resized_images_list[] = array($file, "filesize"=>filesize($path));
                }
            }
            closedir($dh);
        }
    }

    if (!$resized_images_list) return false;
    // Сортируем файлы по их размеру...
    $resized_images_list = array_sort($resized_images_list, 'filesize', SORT_DESC);
    // Удаляем эллемент массива содержащий размер
    foreach($resized_images_list as $key => $value)
    {
        $sorted_resized_images_list[] = $value["0"];
    }

    return $resized_images_list = $sorted_resized_images_list;
}

function array_sort($array, $on, $order = SORT_ASC)
{
    $new_array = array();
    $sortable_array = array();

    if (count($array) > 0) {
        foreach ($array as $k => $v) {
            if (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if ($k2 == $on) {
                        $sortable_array[$k] = $v2;
                    }
                }
            } else {
                $sortable_array[$k] = $v;
            }
        }

        switch ($order) {
            case SORT_ASC:
                asort($sortable_array);
                break;
            case SORT_DESC:
                arsort($sortable_array);
                break;
        }

        foreach ($sortable_array as $k => $v) {
            $new_array[$k] = $array[$k];
        }
    }

    return $new_array;
}

function array_sort_by_filesize($array_with_filenames, $dir_with_files, $order = SORT_ASC)
{
    $sorted_array = array();
    $path = "./". $dir_with_files . "/";

    foreach ($array_with_filenames as $image_name)
    {
        $sorted_array[] = array($image_name, "filesize"=>filesize($path.$image_name));
    }

    $array_with_filenames = array();
    $sorted_array = array_sort($sorted_array, 'filesize', $order);

    foreach($sorted_array as $key => $value)
    {
        $array_with_filenames[] = $value["0"];
    }

    return $array_with_filenames;
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
    return "\r\nВсе изображения успешно скопированы\r\n";
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
        if (@filesize($image_path) == 0)
        {
            echo ">>>>>>>>>>> ФАЙЛ {$image_path} С НУЛЕВЫМ РАЗМЕРОМ ===>>> УДАЛЯЕМ!!! <<<<<<<<<<< \r\n";
            unset($image_name);
            @unlink($image_path);
            continue;
        }
        $file_size_before = round((filesize($image_path) / 1024), 2);
        echo "\r\nИспользую API ключ {$api_key[0]}, ещё возможно использований: {$api_key[1]} \r\n";
        echo "Пережимаю файл [" . $file_counter . "] из [" . count($renamed_list_to_copy) . "] $image_path \r\n";
        echo "Исходный размер = $file_size_before Kb => ";

        try
        {
        \Tinify\setKey($api_key[0]);
        $resized_img = \Tinify\fromFile($image_path)->preserve("copyright", "creation", "location")->toFile($resized_file_path);
        } catch (\Tinify\Exception $e)
        {
            tinifyImg($renamed_list_to_copy, $dir_to_resize);
        }

        $file_size_after = round((filesize($resized_file_path) / 1024), 2);
        $resize_percent = round((($file_size_after/$file_size_before)-1)*100*(-1),2);
        echo "Размер после пережатия  = $file_size_after Kb. ";
        echo "Процент пережатия = ". $resize_percent . "% \r\n";
        
        
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

function LostImagesNameList($lost_images)
{
    // Если во время пережима произошла ошибка и в папке с пережатыми картинками содержатся файлы "resized_"
    foreach($lost_images as $key => $value)
        {
            $lost_images[$key] = str_replace("resized_", '', $lost_images[$key]);
        }
    return $lost_images;
    // END Если во время пережима произошла ошибка и в папке с пережатыми картинками содержатся файлы "resized_"
}

function getWorkData ($resized_directory = "testing", $dir_to_search = "img", $dir_to_resize = "resized")
{
    if (!file_exists('list_of_images.csv'))
    {
        if ($dir_to_search == '.') $message = find_files('.'); // если пришла точка - ищем по всем файлам срвера
        else $message = find_files("./" . $dir_to_search); // иначе ищем в указанной папке
        if ($message) {
            echo $message . "\r\n";
        }
//        return;
    }

    $csv_data = readCSV();

    // creating array for catalog creation
    $images_directories[] = '';
    foreach ($csv_data as $content)
    {
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


    echo copyImgForResize($images_path, $dir_to_resize, $renamed_images_name_list); // Копируем картинки в папку для последующего ресайза

    $resized_images_list = resizedImagesList($dir_to_resize); // дальше ищем эллементы этого списка в $imagess_path при помощи array_intersect($imagess_path, $resized_images_list). При совпадении в ключах будем иметь значения совпавших ключей - по ключам копируем эту картинку по новому пути.


    $renamed_list_to_copy = array_intersect($renamed_images_name_list, $resized_images_list);
    $renamed_list_to_copy = array_sort_by_filesize($renamed_list_to_copy, $dir_to_resize, "SORT_DESC");

    try {
        if($work_done = tinifyImg($renamed_list_to_copy, $dir_to_resize)) echo "\r\nВсе картинки пережаты\r\n";
    } catch (Exception $e) {
        tinifyImg($renamed_list_to_copy, $dir_to_resize);
    }


    if (createDirectories($images_directories, $resized_directory)) echo "Все директории успешно созданы\r\n";


    foreach ($renamed_list_to_copy as $key => $data)
    {
        copyImgWithStructure($images_path[$key], $renamed_images_name_list[$key], $dir_to_resize, $resized_directory);
    }

    $is_resizedir_not_empty = count(glob("{$dir_to_resize}/*")) ? true : false;
    if ($is_resizedir_not_empty)
    {
        $lost_images_names = resizedImagesList($dir_to_resize);
        $lost_images_names = LostImagesNameList($lost_images_names);
        $renamed_list_to_copy = array_intersect($renamed_images_name_list, $lost_images_names);

        foreach ($renamed_list_to_copy as $key => $data)
        {
            copyImgWithStructure($images_path[$key], $renamed_list_to_copy[$key], $dir_to_resize, $resized_directory);
        }
    }
    if(rmdir($dir_to_resize)) echo "Рабочая директория удалена\r\n";
    echo "Работа завершена\r\n";
    return;
}

getWorkData("images-resize-completed", "img", "images-for-resize"); // Папка куда ложим файлы, папка где ищем картинки, папка откуда подтягиваем обработанные картинки

// Перезапускаем функцию при возникновении критических ошибок PHP
//register_shutdown_function(shutdown());
function shutdown()
{
	echo "PHP FATAL ERROR!!! RESTARTING PROCESS...";
    for($i=0;$i<1000;$i++)
    {
        getWorkData("images-resize-completed", "img", "images-for-resize");
    }
	echo "PHP FATAL ERROR!!! RESTART ENDS...";
}
// END Перезапускаем функцию при возникновении критических ошибок PHP