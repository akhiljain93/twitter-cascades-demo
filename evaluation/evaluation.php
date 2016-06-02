<title>Evaluate cascade summaries</title>
<head>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
</head>
<link rel="stylesheet" href="../bootstrap/bootstrap.min.css">

<div class='container'>
    <div class="page-header">
        <h1>Summarising twitter cascades</h1> 
    </div>
    <form action="evaluation.php" method="post" role="form">
        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" name="name" class="form-control">
        </div>
        <div class="form-group">
            <label for="cascade">Cascade number</label>
            <select name="cascade" class="form-control">
                <?php
                    for ($x = 0; $x <= 50; $x++) {
                        echo '<option value="'.$x.'">'.$x.'</option>';
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
                        echo '<select name="entity_'.($row - 1).'">';
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
        <button type="submit" class="btn btn-default" name="submit">Submit</button>
    </form>
</div>
