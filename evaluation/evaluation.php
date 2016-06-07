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

        if (! get_magic_quotes_gpc()) {
            $name = addslashes($_POST['name']);
            $email = addslashes($_POST['email']);
            $summary = addslashes($_POST['summary']);
            $user_interface = addslashes($_POST['ui']);
            $presentation = addslashes($_POST['presentation']);
        } else {
            $name = $_POST['name'];
            $email = $_POST['email'];
            $summary = $_POST['summary'];
            $user_interface = $_POST['ui'];
            $presentation = $_POST['presentation'];
        }

        $cascade = intval($_GET['cascade']);

        $values = "$cascade, '$name', '$email', '$presentation', '$summary', '$user_interface'";
        $columns = "cascade_no, name, email, presentation, summary, user_interface";

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
            <legend>Keywords - Rate the importance</legend>
            <?php
                $handle = fopen("../entity_score/reps/rep_".$_GET['cascade'].".csv", "r");
                $row = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row > 0) {
                    ?>
                        <div class="form-group">
                    <?php
                        echo '<label for="entity_'.$row.'">'.explode(".", $data[1])[1].'</label>';
                        echo '<select name="entity_'.$row.'" class="form-control">';
                        for ($x = 5; $x > 0; $x--) {
                            echo '<option value="'.$x.'">'.$x.'</option>';
                        }
                    ?>
                            </select>
                        </div>
                    <?php
                    }
                    $row++;
                }
                fclose($handle);
            ?>
        </fieldset>
        <fieldset>
            <legend>Keywords - representative tweets - rate the correctness</legend>
            <?php
                $handle = fopen("../entity_score/reps/rep_".$_GET['cascade'].".csv", "r");
                $row = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row > 0) {
                    ?>
                        <div class="form-group">
                    <?php
                        echo '<label for="tweet_'.$row.'">'.explode(".", $data[1])[1].' - "';
                        for ($x = 3; $x < count($data); $x++) {
                            echo $data[$x];
                        }
                        echo '"</label>';
                        echo '<select name="tweet_'.$row.'" class="form-control">';
                        echo '<option value="correct">Correct</option>';
                        echo '<option value="incorrect">Incorrect</option>';
                    ?>
                            </select>
                        </div>
                    <?php
                    }
                    $row++;
                }
                fclose($handle);
            ?>
        </fieldset>
        <fieldset>
            <legend>Remarks and feedback</legend>
            <label for="summary">Remarks on summary</label>
            <textarea class="form-control" rows="5" id="summary"></textarea>
            <label for="ui">Remarks on user interface</label>
            <textarea class="form-control" rows="5" id="ui"></textarea>
            <label for="presentation">Remarks on presentation</label>
            <textarea class="form-control" rows="5" id="presentation"></textarea>
        </fieldset>
        <br>
        <button type="submit" class="btn btn-default" name="submit">Submit</button>
    </form>
    <?php
        }
    ?>
</div>
