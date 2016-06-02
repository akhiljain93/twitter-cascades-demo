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
    // TODO: put all the data into the database
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
            <label for="cascade">Cascade number</label>
            <select name="cascade" class="form-control">
            <?php
                for ($x = 0; $x <= 50; $x++) {
                    echo '<option value="'.$x.'"';
                    if($_GET['cascade'] == $x) {
                        echo ' selected="selected"';
                    }
                    echo '>'.$x.'</option>';
                }
            ?>
             </select>
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
                        echo '<label for="entity_'.($row - 1).'">'.explode(".", $data[1])[1].'</label>';
                        echo '<select name="entity_'.($row - 1).'" class="form-control">';
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
                        echo '<label for="tweet_'.($row - 1).'">'.explode(".", $data[1])[1].' - "';
                        for ($x = 3; $x < count($data); $x++) {
                            echo $data[$x];
                        }
                        echo '"</label>';
                        echo '<select name="tweet_'.($row - 1).'" class="form-control">';
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
