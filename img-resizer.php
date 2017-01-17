<?php
if(in_array($_SERVER['REMOTE_ADDR'], Array('94.244.17.6')))
{
    ini_set('memory_limit','-1');
    ini_set('display_errors','on');
    ini_set('display_startup_errors','on');
    ini_set('max_execution_time', '0');
    ini_set('set_time_limit', '0');
    error_reporting(E_ALL);
}


//find_files('./img');

// removes files and non-empty directories
function rrmdir($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        foreach ($files as $file)
            if ($file != "." && $file != "..") rrmdir("$dir/$file");
//        rmdir($dir);
    }
    else if (file_exists($dir)) /*unlink($dir)*/;
}

// copies files and non-empty directories
function rcopy($src, $dst) {
    if (file_exists($dst)) {echo "directory {$dst} already exists \n"; /* rrmdir($dst) */;}
    if (is_dir($src))
    {
        echo "Создаю директорию {$dst}\n";
        mkdir($dst);
        $files = scandir($src);
        foreach ($files as $file)
            if ($file != "." && $file != "..") rcopy("$src/$file", "$dst/$file");
    }
    elseif (file_exists($src))
    {
        echo "делаю копию {$src} в {$dst}\n";
        copy($src, $dst);
    }
}
/*function find_files($seed)
{
    if(! is_dir($seed)) return false;
    $files = array();
    $dirs = array($seed);
    while(NULL !== ($dir = array_pop($dirs)))
    {
        if($dh = opendir($dir))
        {
            while( false !== ($file = readdir($dh)))
            {
                if($file == '.' || $file == '..') continue;
                $path = $dir . '/' . $file;
                var_dump($dir);

                if(is_dir($path)) { $dirs[] = $path; }
            }
            closedir($dh);
        }
    }
}*/
function find_files($seed, $dir_for_resizing)
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
                //rcopy ($dir, $destination);
                if (preg_match('/^.*\.(jpe?g|png|gif)$/i', $path))
                {
//                    var_dump($rand_dir_arr);
//                    echo "\r\n";
//                    echo "$dir" . '/' . "$file \r\n";
                    $rand_file_name = getRandFileName($dir, $file); // получаем случайное имя картинки
                    createCSV($dir, $file, $rand_file_name);
                }
                if(is_dir($path)) { $dirs[] = $path; }

            }
        closedir($dh);
        }
    }

    return $message = "CSV файл успешно создан <br />\r\n Для продолжения нажмите <span style=\"font-weight:bold; color:red;\">SHIFT+F5</span><br />\r\n";
}

function copyImgForResize($images_path, $dir_to_resize, $renamed_images_name_list)
{
    if (!is_dir('./'.$dir_to_resize.'/')) mkdir('./'.$dir_to_resize.'/');
    $new_images_path = './' . $dir_to_resize . '/';
    for ($i=0; $i<count($images_path); $i++)
    {
        if(is_file($images_path[$i]) && !is_file($new_images_path.$renamed_images_name_list[$i]))
        {
            if(!copy($images_path[$i], $new_images_path . $renamed_images_name_list[$i]))
            {
                echo "Не удалось скопировать {$images_path[$i]}\r\n";
            } elseif(copy($images_path[$i], $new_images_path . $renamed_images_name_list[$i])) {
                echo "Успешно скопирован файл [$i] {$images_path[$i]} в => {$new_images_path}{$renamed_images_name_list[$i]} <br />\r\n";
            } else createCSV($images_path[$i], $new_images_path.$renamed_images_name_list[$i], $i, "erorr_file.csv");
        }
    }
    return "\r\n<span style=\"font-weight:bold; color:red;\">Все изображения успешно скопированы</span> <br />\r\n";
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
//    $filename = 'list_of_images.csv';
    $CSVdata = "$image_name;$path;$rand_file_name;\r\n";
    file_put_contents($filename, $CSVdata, FILE_APPEND);
// !!!!!!!!!!!!!! //    echo "file $image_name had been written with $path \r\n";
}
// END Function which creates an CSV file

function readCSV ($filename = 'list_of_images.csv')
{
    $csv_content = file($filename);
    foreach ($csv_content as $key => $content)
    {
        $csv[] = explode(";", $content);
        unset($csv[$key][3]);
    }
    return $csv;
}

function getWorkData ($resized_directory = "testing", $dir_to_search = "img", $dir_to_resize = "resized")
{
    if (!file_exists('list_of_images.csv')) {
        if ($dir_to_search == '.') $message = find_files('.', $dir_to_resize); // если пришла точка - ищем по всем файлам срвера
        else $message = find_files("./" . $dir_to_search, $dir_to_resize); // иначе ищем в указанной папке
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

        echo copyImgForResize($images_path, $dir_to_resize, $renamed_images_name_list); // Копируем картинки в папку для последующего ресайза

    if(!isset($_GET['upload'])) echo "Для копирования файлов из директории {$dir_to_resize} в директорию $resized_directory <a href=\"http://lvivskiy-maetok.com.ua/img-resizer.php?upload\"><span style=\"font-weight:bold; color:red;\">ПЕРЕЙДИТЕ ПО ССЫЛКЕ</span></a><br /> \r\n";

    if(isset($_GET['upload']))
        {
            $resized_images_list = resizedImagesList($dir_to_resize); // дальше ищем эллементы этого списка в $imagess_path при помощи array_intersect($imagess_path, $resized_images_list). При совпадении в ключах будем иметь значения совпавших ключей - по ключам копируем эту картинку по новому пути.
            $renamed_list_to_copy = array_intersect($renamed_images_name_list, $resized_images_list);
//var_dump(array_unique($resized_images_list));die();
                /*$found = array();
                $not_found = array();

            foreach ($resized_images_list as $resized_image)
            {
                if (!in_array($resized_image, $renamed_images_name_list))
                {
                    $not_found[] = "Элемент $resized_image не найден \r\n";
                } elseif (in_array($resized_image, $renamed_images_name_list))
                {
                    $found[] = "Элемент $resized_image найден \r\n";
                }
            }
            var_dump($not_found);
            echo "\r\n-----------------------------------------------------\r\n";
            echo "-----------------------------------------------------\r\n";
            echo "-----------------------------------------------------\r\n";
            var_dump($found);*/

//die();
            if (createDirectories($images_directories, $dir_to_resize, $resized_directory)) echo "<span style=\"font-weight:bold; color:red;\">Все директории успешно созданы</span> <br />\r\n";

            //var_dump($images_to_resize_path);
            foreach ($renamed_list_to_copy as $key => $data) {
                copyImgWithStructure($images_path[$key], $renamed_images_name_list[$key], $dir_to_resize, $resized_directory);
            }
            echo "<span style=\"font-weight:bold; color:red;\">Работа завершена</span><br />";
        }
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
                if (preg_match('/^.*\.(jpe?g|png|gif)$/i', $path))
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

function createDirectories($images_directories, $dir_to_resize, $resized_dir)
{
//    $dir_to_resize = './' . $dir_to_resize;
    $resized_dir = './' . $resized_dir;
    foreach ($images_directories as $directory)
    if (!is_dir($directory))
    {
        $piece_of_dir = explode("/", $directory);
//        $piece_of_dir_to_resize = $dir_to_resize;
        $piece_of_dir_resized = $resized_dir;
        foreach ($piece_of_dir as $key => $value)
        {
//            $piece_of_dir_to_resize .= $value . "/";
            $piece_of_dir_resized .= $value . "/";
//            if(!is_dir($piece_of_dir_to_resize) && !mkdir($piece_of_dir_to_resize)) echo "НЕ МОГУ СОЗДАТЬ ПАПКУ \"$piece_of_dir_to_resize\" !!!\r\n";
            if(!is_dir($piece_of_dir_resized) && !mkdir($piece_of_dir_resized)) echo "НЕ МОГУ СОЗДАТЬ ПАПКУ \"$piece_of_dir_resized\" !!!\r\n";
        }
    }
    return true;
}

function copyImgWithStructure ($images_path, $renamed_images_name_list, $dir_to_resize, $resized_directory)
{
    static $file_copy_counter = 0;
    $images_resized_path = "./" . $dir_to_resize . "/" . $renamed_images_name_list;
    $images_new_path = str_replace("./", "./". $resized_directory ."/", $images_path);
//    var_dump($images_new_path); die();
    if(!copy($images_resized_path, $images_new_path))
    {
        echo "Успешно скопирован файл [{$file_copy_counter}] из $images_resized_path в => $images_new_path \r\n";
        createCSV ($file_copy_counter, $images_resized_path, $images_new_path, 'work-done-list.csv');
    }elseif(!copy($images_resized_path, $images_new_path))
    {
        echo "Ошибка копирования файла [{$file_copy_counter}] из $images_resized_path в => $images_new_path \r\n";
    }
    $file_copy_counter++;
}
//mkdir("./Big_Words");


getWorkData("images-resize-completed", "img", "images-for-resize"); // Папка куда ложим файлы, папка где ищем картинки, папка откуда подтягиваем обработанные картинки