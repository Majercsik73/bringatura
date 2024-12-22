<?php

require_once "views/vendor/autoload.php";
require_once "basicGlobalRoutes.php";
require_once "globalRoutesByKm.php";
use Dompdf\Dompdf;


function routeListDatas($start, $touching, $end)
{
    $pdo = getConnection();
    
    // 8 * 10 * 12
    if ($start < $touching && $touching < $end)
    {
        $statement = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND ?');
        $statement->execute([$start, $end]);
        $telepulesek = $statement->fetchAll(PDO::FETCH_ASSOC);
        //Adatok csúsztatása
        $telepulesek[0]['tav'] = 0;
        $telepulesek[0]['utszam'] = '----';
        $telepulesek[0]['fel'] = 0;
        $telepulesek[0]['le'] = 0;
    }
    // 142 * 146 * 5   // 136 * 1 *10
    elseif (($start < $touching && $touching > $end && $start > $end) || ($start > $end && $touching == 1))
    {
        $statement1 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN 2 AND ? ');
        $statement1->execute([$end]);
        $telepulesek1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

        $statement2 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND 148');
        $statement2->execute([$start]);
        $telepulesek2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $telepulesek = array_merge($telepulesek2, $telepulesek1);
        //Adatok csúsztatása
        $telepulesek[0]['tav'] = 0;
        $telepulesek[0]['utszam'] = '----';
        $telepulesek[0]['fel'] = 0;
        $telepulesek[0]['le'] = 0;
        //$telepulesek = dataSlider($telepulesek3);
    }

    // 112 * 80 * 70
    elseif($start > $touching && $touching > $end)
    {
        $statement = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND ? ORDER BY id DESC');
        $statement->execute([$end, $start]);
        $telepulesek4 = $statement->fetchAll(PDO::FETCH_ASSOC);
        
        $telepulesek = dataSlider($telepulesek4); //Adatok csúsztatása
        $telepulesek = dataChange($telepulesek);  // a 'fel' - 'le' adatok cseréje az irányváltás miatt 
    }
    // 12 * 5 * 140
    else
    {
        $statement1 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN 2 AND ? ORDER BY id DESC');
        $statement1->execute([$start]);
        $telepulesek1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

        $statement2 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND 148 ORDER BY id DESC');
        $statement2->execute([$end]);
        $telepulesek2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $telepulesek3 = array_merge($telepulesek1, $telepulesek2);  // a két tömb összeillesztése
        $telepulesek = dataSlider($telepulesek3);  //Adatok csúsztatása
        $telepulesek = dataChange($telepulesek);  // a 'fel' - 'le' adatok cseréje az irányváltás miatt
    }
    
    return $telepulesek;
   
}

function getPlanningDatas($id)
{
    $pdo = getConnection();
    
    $statement = $pdo->prepare('SELECT * FROM datas WHERE id = ?');
    $statement->execute([$id]);
    $datas = $statement->fetchAll(PDO::FETCH_ASSOC);
    //echo "<pre>";
    //var_dump($datas);

    return $datas;
}

function updateRoutePlanningHandler()
{
    session_start();

    $id = $_SESSION['userId'];
    $start = $_SESSION['start'];
    $touching = $_SESSION['touching'];
    $end = $_SESSION['end'];
    
    $pdo = getConnection();
    $statement = $pdo->prepare('UPDATE `datas` SET `start`= ?,`touching`= ?,`end`= ? WHERE id = ?');
    $statement ->execute([$start, $touching, $end, $id]);

    header('Location:/Bringatura_MKK/utvonal?start='.$start.'&touching='.$touching.'&end='.$end.'&info=savedDatas');
}

function routeListHandler()
{
    session_start();

    $_SESSION['start'] = $_GET["start"];
    $_SESSION['touching'] = $_GET["touching"];
    $_SESSION['end'] = $_GET["end"];

    $start = $_GET["start"];
    $touching = $_GET["touching"];
    $end = $_GET["end"];

    $telepulesek = routeListDatas($start, $touching, $end);
    
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routeList.phtml',[
            'telepulesek' => $telepulesek,
            'start' => $start,
            'touching' => $touching,
            'end' => $end,
            'info' => $_GET['info'] ?? ''
            //'osszesTelepules' => $osszesTelepules,
        ]),
        'isAuthorized' => isLoggedIn(), //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}

function savedRouteHandler()
{
    session_start();
    $userid = $_SESSION["userId"];
    //$content = file_get_contents("./datasList.json");
    $datas = getPlanningDatas($userid); //json_decode($content, true);
        
    $start = $datas[0]["start"]; //$_GET["startId"];
    $touching = $datas[0]["touching"]; //$_GET; //$_GET["touchId1"];
    $end = $datas[0]["end"]; //$_GET; //$_GET["touchId2"];

    $telepulesek = routeListDatas($start, $touching, $end);
    
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routeList.phtml',[
            'telepulesek' => $telepulesek,
            'start' => $start,
            'touching' => $touching,
            'end' => $end,
            'info' => $_GET['info'] ?? ''
            //'osszesTelepules' => $osszesTelepules,
        ]),
        'isAuthorized' => isLoggedIn(), //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);

}


function routeListToPdfHandler()
{
    $dompdf = new Dompdf();

    $dompdf->set_option('enable_remote', TRUE);
    $dompdf->loadHtmlFile('http://localhost/Bringatura_MKK/routeListPdf?start='.
                            $_POST['start'].'&touching='.$_POST['touching'].'&end='.$_POST['end']);
    // A fenti címmel a generatRouteListToPdfHandlerben történik a tényleges megjelenítés
    
    $dompdf->setPaper('A4', 'landscape');
    
    $dompdf->render();
    
    $dompdf->stream('negyedik.pdf', array("Attachment"=>0)); // Attachment => nem menti egyből, külön lapon megnyitja
    
}


function generateRouteListToPdfHandler()
{
    session_start();

    // Az adatok a routeListToPdfHandler-ből jönnek:
    $start = $_GET["start"];
    $touching = $_GET["touching"];
    $end = $_GET["end"];
    
    $telepulesek = routeListDatas($start, $touching, $end);
    
    echo compileTemplate('PdfWrapper.phtml', [
        'content' => compileTemplate('routePdfSite.phtml',[
            'telepulesek' => $telepulesek,
            'start' => $start,
            'touching' => $touching,
            'end' => $end
        ]),
        'isAuthorized' => isLoggedIn(), //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}
?>