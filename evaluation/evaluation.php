<title>Evaluate cascade summaries</title>
<script type="text/javascript">
    function checkForm(form) {
        var x = form.email.value;
        var atpos = x.indexOf("@");
        var dotpos = x.lastIndexOf(".");
        if (atpos < 1 || dotpos < atpos + 2 || dotpos + 2 >= x.length) {
            alert("Not a valid e-mail address");
            form.email.focus();
            return false;
        }
        if(!form.captcha.value.match(/^\d{5}$/)) {
            alert('Please enter the CAPTCHA digits in the box provided');
            form.captcha.focus();
            return false;
        }
        return true;
    }
</script>
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
        session_start();
        if ($_POST['captcha'] != $_SESSION['digit']) {
            die("Sorry, the CAPTCHA code entered was incorrect!");
        }
        session_destroy();

        $servername = "localhost";

        $credentials = fopen("../credentials.txt", "r");
        $user = trim(fgets($credentials));
        $password = trim(fgets($credentials));
        $database = trim(fgets($credentials));
        $conn = mysql_connect($servername, $user, $password);

        if (!$conn) {
            die("Could not connect: ".mysql_error());
        }

        if (!get_magic_quotes_gpc()) {
            $name = htmlspecialchars(addslashes($_POST['name']));
            $email = htmlspecialchars(addslashes($_POST['email']));
            $miss = htmlspecialchars(addslashes($_POST['miss']));
            $summary = htmlspecialchars(addslashes($_POST['summary']));
        } else {
            $name = htmlspecialchars($_POST['name']);
            $email = htmlspecialchars($_POST['email']);
            $miss = htmlspecialchars($_POST['miss']);
            $summary = htmlspecialchars($_POST['summary']);
        }

        $cascade = intval($_GET['cascade']);
        $ease = intval($_POST['ease']);

        $values = "$cascade, '$name', '$email', '$miss', $ease, now(), '$summary'";
        $columns = "cascade_no, name, email, missing_entities, ease_of_use, timestamp, summary";

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
    <a href=../entity_score/master.php type="button" class="btn btn-success">Back to Master</a>
    <?php
        } else {
    ?>
    <form action="" method="post" role="form" onsubmit="return checkForm(this);">
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
                        $entity = explode(".", $data[1]);
                        echo '<td><label for="entity_'.$row.'"><a href="../entity_score/index.html?cascade='.$_GET['cascade'].'&scope='.($row - 1).'" target="_blank">'.$entity[1].'</a></label></td>';
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
            <div class="form-group">
                <label for="miss">Were there any entities important to the cascade that you think our system missed? (Please enter one entity per line)</label>
                <textarea class="form-control" rows="5" name="miss"></textarea>
            </div>
            <div class="form-group">
                <label for="ease">How much more likely are you to use EasyBrowse as compared to the traditional style of browsing twitter (BaseView)?</label>
                <select name="ease" class="form-control">
                <?php
                    for ($x = 2; $x > -3; $x--) {
                        echo '<option value="'.$x.'">'.$x;
                        if ($x == 2) {
                            echo ' - EasyBrowse is much better than BaseView';
                        } else if ($x == 0) {
                            echo ' - EasyBrowse is similar to BaseView';
                        } else if ($x == -2) {
                            echo ' - EasyBrowse is much worse than BaseView';
                        }
                        echo '</option>';
                    }
                ?>
                </select>
            </div>
        </fieldset>
        <fieldset>
            <legend>Verify you are human</legend>
            <div class="form-group">
                <label for="summary">Please write a short (2 line) summary of the cascade.</label>
                <textarea class="form-control" rows="5" name="summary" required></textarea>
            </div>
            <img src="./captcha.php" width="400" height="100" border="1" alt="CAPTCHA" class="img-thumbnail center-block">
            <div class="form-group">
                <label for="captcha">Copy the digits from the image into this box</label>
                <input type="text" class="form-control" name="captcha" value="" required>
            </div>
        </fieldset>
        <p class="pager"><button type="submit" class="btn btn-default" name="submit">Submit</button></p>
    </form>
    <?php
        }
    ?>
</div>
