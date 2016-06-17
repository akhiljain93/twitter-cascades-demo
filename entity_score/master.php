<?php
    function printRow($x, $conn, $foreign_cascades) {
        echo '<tr>';
        echo '<td><a href=cascade.html?cascade='.$x.'>BV '.$x.'</a></td>';
        echo '<td><a href=index.html?cascade='.$x.'>EB '.$x.'</a></td>';
        echo '<td>';
        if (in_array($x, $foreign_cascades)) {
            echo 'Foreign Language Cascade';
        } else {
            $handle = fopen("../entity_score/reps/rep_".$x.".csv", "r");
            $row = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($row > 0) {
                    if ($row > 1) {
                        echo ', ';
                    }
                    $entity = explode(".", $data[1]);
                    echo '<a href=index.html?cascade='.$x.'&scope='.($row - 1).'>'.$entity[1].'</a>';
                }
                $row++;
            }
        }
        echo '</td>';
        $search_query = "select count(*) from results where cascade_no=".$x;
        $result = mysql_query($search_query, $conn);
        if (!$result) {
            die('Could not run query: '.mysql_error());
        }
        echo '<td>'.mysql_result($result, 0).'</td>';
        echo '</tr>';
    }
?>
<title>EasyBrowse</title>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
</head>
<body>
    <h1>EasyBrowse - the Twitter Cascade Summarisation project</h1>
    <p>Please use 'Ctrl + F' to search for a keyword of interest.</p>
    <table border="1" style="width:50%; float: left">
        <tr>
            <th>BaseView</th>
            <th>EasyBrowse</th>
            <th>Major entities</th>
            <th>Evaluation count</th>
        </tr>
        <?php
            $servername = "localhost";
            $user = "user";
            $password = "password";
            $database = "database";
            $conn = mysql_connect($servername, $user, $password);

            if (!$conn) {
                die("Could not connect: ".mysql_error());
            }

            mysql_select_db($database) or die("Unable to select database");

            $foreign_cascades = array(3, 8, 15, 22, 33, 42);

            for ($x = 0; $x <= 24; $x++) {
                printRow($x, $conn, $foreign_cascades);
            }
        ?>

    </table>
    <table border="1" style="width:50%; float: left">
        <tr>
            <th>BaseView</th>
            <th>EasyBrowse</th>
            <th>Major entities</th>
            <th>Evaluation count</th>
        </tr>
        <?php
            for ($x = 25; $x <= 50; $x++) {
                printRow($x, $conn, $foreign_cascades);
            }
            mysql_close($conn);
        ?>
    </table>
</body>
