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
            $summary = htmlspecialchars(addslashes($_POST['summary']));
            $user_interface = htmlspecialchars(addslashes($_POST['ui']));
            $presentation = htmlspecialchars(addslashes($_POST['presentation']));
        } else {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $summary = htmlspecialchars($_POST['summary']);
            $user_interface = htmlspecialchars($_POST['ui']);
            $presentation = htmlspecialchars($_POST['presentation']);
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
            <legend>Keywords - representative tweets - rate the correctness</legend>
            <table class="table table-bordered table-hover">
            <tr>
                <th>Entity</th>
                <th>Important tweet</th>
                <th>Entity importance</th>
                <th>Tweet identification</th>
            </tr>
            <?php
                $handle = fopen("../entity_score/reps/rep_".$_GET['cascade'].".csv", "r");
                $row = 0;
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if ($row > 0) {
            ?>
                <tr>
                    <?php
                        echo '<td><label for="entity_'.$row.'">'.explode(".", $data[1])[1].'</label></td>';
                        echo '<td><label for="tweet_'.$row.'">';
                        for ($x = 3; $x < count($data); $x++) {
                            echo $data[$x];
                        }
                    ?>
                        </label></td>
                    <?php
                        echo '<td><select name="entity_'.$row.'">';
                        for ($x = 5; $x > 0; $x--) {
                            echo '<option value="'.$x.'">'.$x.'</option>';
                        }
                    ?>
                        </select></td>
                    <?php
                        echo '<td><select name="tweet_'.$row.'">';
                    ?>
                        <option value="1">Correct</option>
                        <option value="0">Incorrect</option>
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
            <label for="summary">Remarks on summary</label>
            <textarea class="form-control" rows="5" name="summary"></textarea>
            <label for="ui">Remarks on user interface</label>
            <textarea class="form-control" rows="5" name="ui"></textarea>
            <label for="presentation">Remarks on presentation</label>
            <textarea class="form-control" rows="5" name="presentation"></textarea>
        </fieldset>
        <br>
        <button type="submit" class="btn btn-default" name="submit">Submit</button>
    </form>
    <?php
        }
    ?>
</div>
