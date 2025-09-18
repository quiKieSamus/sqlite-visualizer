<?php
$database = null;
$tables = [];
$rows = [];
/**
 * @var Exception[]
 */
$errors = [];
$affected_rows = 0;
try {
    if (isset($_GET["option"]) && $_GET["option"] == "refresh" && file_exists("./database.sqlite")) {
        unlink("./database.sqlite");
    }

    if ($database == null && isset($_FILES["path"]) || isset($_GET["file"])) {
        move_uploaded_file($_FILES["path"]["tmp_name"], "./database.sqlite");
        $database = new PDO("sqlite:/xampp/htdocs/sqlite-visualizer/database.sqlite");
        $tables = $database->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    }

    if ($database == null && file_exists("./database.sqlite")) {
        $database = new PDO("sqlite:/xampp/htdocs/sqlite-visualizer/database.sqlite");
        $tables = $database->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll();
    }

    if (isset($_GET["table"])) {
        $rows = $database->query("SELECT * FROM " . $_GET["table"])->fetchAll();
    }

    if ($database !== null && isset($_POST["sql-editor"])) {
        if (str_starts_with(strtolower($_POST["sql-editor"]), "select")) {
            $rows = $database->query($_POST["sql-editor"])->fetchAll();
        } else {
            $affected_rows = $database->exec($_POST["sql-editor"]);
        }
    }
} catch (Exception $e) {
    $errors[] = $e;
}

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQLITE VISUALIZER</title>
</head>

<body class="bg-dark text-light">
    <?php
    if (count($errors) > 0) {
        foreach ($errors as $error) {
            echo "<div class='w-100 position-absolute top-50 right-50'><span class='alert alert-danger'>{$error->getMessage()}</span></div>";
        }
    }

    if (file_exists("./database.sqlite")) {
        echo "<a href='{$_SERVER['PHP_SELF']}?option=refresh'>Cargar otra base de datos</a>";
    } else {
        echo '<form action="' . $_SERVER["PHP_SELF"] . '" method="post" enctype="multipart/form-data">
                <input type="file" name="path">
                <input type="submit">
            </form>';
    }

    ?>

    <?php

    if ($database !== null) {
        echo '    
        <div class="sql-editor container-fluid">
            <form method="post" action="' . $_SERVER['PHP_SELF'] . '?table=' . (isset($_GET['table']) ? $_GET['table'] : "nada") . '">
                <textarea name="sql-editor" class="w-100 form-control"></textarea>
                <input type="submit" value="Run" class="btn btn-primary">
            </form>
        </div>
    ';
    }

    ?>

    <div class="table-container container gap-3 d-flex justify-content-evenly">
        <div class="table-names-panel">
            <p class="display-6">Tables:</p>
            <?php
            foreach ($tables as $table) {
                echo "<div><a href=$_SERVER[PHP_SELF]?table=$table[name]>{$table['name']}</a></div>";
            }
            ?>
        </div>
        <div class="container">
            <p class="display-6">Stats:</p>
            <p>Rows fetched: <?= count($rows) ?></p>
            <p>Rows affected: <?= $affected_rows ?></p>
        </div>
        <div class="table-responsive tables-data-panel w-100">
            <?php
            if (count($rows) <= 0) {
                echo "No table selected";
            } else {
                $columns = array_keys($rows[0]);
                $columns = array_filter($columns, fn($item) => !is_numeric($item));
            ?>
                <table class="table table-dark table-striped">
                    <thead>
                        <tr>
                            <?php
                            foreach ($columns as $column) {
                                echo "<td>{$column}</td>";
                            }
                            ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($rows as $row) {
                            echo "<tr>";
                            foreach ($columns as $column) {
                                echo "<td>{$row[$column]}</td>";
                            }
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

            <?php
            }
            ?>
        </div>
    </div>
    <script>
        setTimeout(() => {
            document.querySelectorAll(".alert").forEach(item => item.remove());
        }, 5000);
    </script>
</body>

</html>