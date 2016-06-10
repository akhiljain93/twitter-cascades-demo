<title>Evaluate cascade summaries</title>
<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
    <link rel="stylesheet" href="../bootstrap/bootstrap.min.css">
</head>
<div class='container'>
    <div class="page-header">
        <h1>Summarising twitter cascades</h1> 
    </div>
    <?php
    if (isset($_POST['submit'])) {
        $servername = "localhost";
        $user = "root";
        $password = "root";
        $database = "evaluation_data";
        $conn = mysql_connect($servername, $user, $password);

        if (!$conn) {
            die("Could not connect: ".mysql_error());
        }

        if (!get_magic_quotes_gpc()) {
            $name = htmlspecialchars(addslashes($_POST['name']));
            $email = htmlspecialchars(addslashes($_POST['email']));
            $miss = htmlspecialchars(addslashes($_POST['miss']));
        } else {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $miss = htmlspecialchars($_POST['miss']);
        }

        $cascade = intval($_GET['cascade']);
        $ease = intval($_POST['ease']);

        $values = "$cascade, '$name', '$email', '$miss', $ease";
        $columns = "cascade_no, name, email, missing_entities, ease_of_use";

        for ($x = 1; $x <= 10; $x++) {
            $tweets = intval($_POST['tweet_'.$x]);
            $entities = intval($_POST['entity_'.$x]);
            $columns = $columns.", entity_".$x.", entity_tweet_".$x;
            $values = $values.", $entities, $tweets";
        }

        mysql_select_db($database) or die("Unable to select database");
        $insert_query = "insert into results($columns) values ($values)";
        $retval = mysql_query($insert_query, $conn);
        if (!$retval) {
            die('Could not enter data: '.mysql_error());
        }

        mysql_close($conn);
    ?>
    <h3>Thank you for the evaluation. We will be in touch.</h3>
    <?php
        } else {
    ?>
    <form action="" method="post" role="form">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="form-group">
            <label for="email">Email</label>
            <input type="text" name="email" class="form-control" required>
        </div>
        <fieldset>
            <legend>Keywords - representative tweets - rate the correctness</legend>
            <table class="table table-bordered table-hover">
            <tr>
                <th style="text-align: center">Entity<br>(1)</th>
                <th style="text-align: center">Important tweet<br>(2)</th>
                <th style="text-align: center">How important is the entity in (1) to this cascade?</th>
                <th style="text-align: center">Is (2) the most important tweet for (1)?</th>
            </tr>
            <?php
                $handle = fopen("../entity_score/reps/rep_".$_GET['cascade'].".csv", "r");
                $row = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row > 0) {
            ?>
                <tr>
                    <?php
                        echo '<td><label for="entity_'.$row.'"><a href="../entity_score/index.html?cascade='.$_GET['cascade'].'&scope='.($row - 1).'" target="_blank">'.explode(".", $data[1])[1].'</a></label></td>';
                        echo '<td><label for="tweet_'.$row.'"><a href="../entity_score/index.html?cascade='.$_GET['cascade'].'&tweet='.$data[2].'" target="_blank">';
                        for ($x = 3; $x < count($data); $x++) {
                            echo $data[$x];
                        }
                    ?>
                        </a></label></td>
                    <?php
                        echo '<td><select name="entity_'.$row.'">';
                        for ($x = 5; $x > 0; $x--) {
                            echo '<option value="'.$x.'">'.$x;
                            if ($x == 5) {
                                echo ' - Most important';
                            } else if ($x == 1) {
                                echo ' - Least important';
                            }
                            echo '</option>';
                        }
                    ?>
                        </select></td>
                    <?php
                        echo '<td><select name="tweet_'.$row.'">';
                    ?>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                        </select></td>
                </tr>
            <?php
                    }
                    $row++;
                }
                fclose($handle);
            ?>
            </table>
        </fieldset>
        <fieldset>
            <legend>Remarks and feedback</legend>
            <label for="miss">Were there any entities important to the cascade that you think our system missed?</label>
            <textarea class="form-control" rows="5" name="miss"></textarea>
            <label for="ease">Compared to the traditional style of browsing twitter (BaseView), how useful is EasyBrowse?</label>
            <select name="ease" class="form-control">
            <?php
                for ($x = 5; $x > 0; $x--) {
                    echo '<option value="'.$x.'">'.$x;
                    if ($x == 5) {
                        echo ' - Most useful';
                    } else if ($x == 1) {
                        echo ' - Least useful';
                    }
                    echo '</option>';
                }
            ?>
            </select>
        </fieldset>
        <br>
        <p class="pager"><button type="submit" class="btn btn-default" name="submit">Submit</button></p>
    </form>
    <?php
        }
    ?>
</div>
