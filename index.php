<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv = "Content-Type" content = "text/html; charset=utf-8" >
        <script src="js/float.js"></script>
        <link href = "css/grid.css" type = "text/css" rel = "stylesheet" >
        <link href = "css/float.css" type = "text/css" rel = "stylesheet">
        
        <title></title>
    </head>
    <body>
        <?php
        include 'marc21.php';

        $m21f[0] = 'mrc/Atest.utf8.mrc';
        $m21f[1] = 'mrc/Btest.utf8.mrc';
        $m21f[2] = 'mrc/marc-00000000-00001999.mrc';

        $filter='100|245|020|246';
        if($_GET['nofilter']==1){
            $filter='';
        }
        
        
        $f=1;
        $m21 = new m21File($m21f[$f]);
        $m21->setTagFilter($filter);
        
        echo '<p>&nbsp;';
        $git='All sources at '
        . '<a href="https://github.com/napengam/marc21" style="margin-left:1em;vertical-align:center">GitHub<img src="GitHub.png"></a><br>';  
          
        $h2 = '<h2 id=h2id style="">File='.basename($m21f[$f]).'  filtered by tags 100|245|020|246</h2>'.$git;
        $echo = '<table style="margin-left:10px;width:auto" id=t1 class=tgrid>'
                . '<tr><th class=tgrid_th colspan=4>' . $h2 . '</th></tr>'
                . '<tr><th class=tgrid_th>Tag</th><th data-rotate class=tgrid_th>Indicator</th>'
                . '<th data-rotate class=tgrid_th>Subfield<br>Code</th><th class=tgrid_th> Subfielddata</th></tr>';
        
        $nrec = 0;
        $tags = $m21->decodeRecord();
        while ($tags) {
            $n = count($tags);
            $echo.= '<tr style="background:whitesmoke;"><td></td><td></td><td></td><td style="text-align:right;" >' . ++$nrec . '</td></tr>';
            for ($i = 0; $i < $n; $i++) {
                $echo.= '<tr><td>' . $tags[$i]->tag . '</td><td> ' . $tags[$i]->ind . '</td>';
                $m = count($tags[$i]->subs);
                for ($j = 0, $head = ''; $j < $m; $j++) {                  
                    if ($tags[$i]->subs[$j]->code != '') {                     
                        $tags[$i]->subs[$j]->data = checkForUri($tags[$i]->subs[$j]->data, $tags[$i]->subs[$j]->code);
                    }
                    if ($j > 0) {
                        $head = '<tr><td></td><td></td>';
                    }
                    $echo.= "$head<td align=center>" . $tags[$i]->subs[$j]->code . '</td><td> ' . wordwrap($tags[$i]->subs[$j]->data, 120) . '</td></tr>';
                }
            }
            if (strlen($echo) > 2048 * 100) {
                echo $echo;
                $echo = '';
            }
            $tags = $m21->decodeRecord();
            //$tags = NULL;
        }
        echo $echo;
        echo '</table>';
        echo "<script>var nrecForh2id=$nrec</script>";

        function checkForUri($data, $code) {
            if ($code * 1 === 0) {
                if (mb_substr($data, 0, 5) == '(uri)') {
                    $data = '<a href="' . mb_substr($data, 5) . '">' . $data . '</a>';
                }
            }
            return $data;
        }
        ?>
        <script type="text/javascript">
            function addEvent(obj, ev, fu) {
                if (obj.addEventListener) {
                    obj.addEventListener(ev, fu, false);
                } else {
                    var eev = 'on' + ev;
                    obj.attachEvent(eev, fu);
                }
            }
            addEvent(window, 'load', function () {
                document.getElementById('h2id').innerHTML += '<br>Number of records=' + nrecForh2id;
                floatHeader('t1', {ncpth: [1, 1], nccol: 1, topDif: 000, leftDif: 000});
            });

        </script>
    </body>
</html>
