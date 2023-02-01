<?php

require_once "views/vendor/autoload.php";

use Dompdf\Dompdf;


function routeByKmDatas($start, $touch1, $touch2, $km)
{
    $pdo = getConnection();

    $statement = $pdo->prepare('SELECT * FROM fooldal');
    $statement->execute();
    $osszesTelepules = $statement->fetchAll(PDO::FETCH_ASSOC);
    //echo"<pre>";
    //var_dump($telepulesek);
    // 8 * 10 * 12
    if ($start != 1 && $start < $touch1 && $touch1 < $touch2)
    {
        $statement1 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND 148');
        $statement1->execute([$start]);
        $telepulesek1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

        $statement2 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN 2 AND ? ');
        $statement2->execute([$start]);
        $telepulesek2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $telepulesek = array_merge($telepulesek1, $telepulesek2);
        //Adatok csúsztatása
        $telepulesek[0]['tav'] = 0;
        $telepulesek[0]['utszam'] = '----';
        $telepulesek[0]['fel'] = 0;
        $telepulesek[0]['le'] = 0;
    }
    // 142 * 146 * 5 
    elseif ($start != 1 && $start < $touch1 && $touch1 > $touch2 && $touch2 < $touch1 && $start > $touch2)
    {
        $statement1 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND 148');
        $statement1->execute([$start]);
        $telepulesek1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

        $statement2 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN 2 AND ? ');
        $statement2->execute([$start]);
        $telepulesek2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $telepulesek = array_merge($telepulesek1, $telepulesek2);
        //Adatok csúsztatása
        $telepulesek[0]['tav'] = 0;
        $telepulesek[0]['utszam'] = '----';
        $telepulesek[0]['fel'] = 0;
        $telepulesek[0]['le'] = 0;
        //$telepulesek = dataSlider($telepulesek3);
    }
    // 112 * 80 * 70   140 * 120 * 80
    elseif($start > $touch1 && $touch1 > $touch2 || $start == 1 && $touch1 > $touch2 && $touch2 != $start)
    {
        $statement1 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN 2 AND ? ORDER BY id DESC');
        $statement1->execute([$start]);
        $telepulesek1 = $statement1->fetchAll(PDO::FETCH_ASSOC);
        
        $statement2 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND 148 ORDER BY id DESC');
        $statement2->execute([$start]);
        $telepulesek2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $telepulesek3 = array_merge($telepulesek1, $telepulesek2);
        //Adatok csúsztatása
        $telepulesek = dataSlider($telepulesek3);
        $telepulesek = dataChange($telepulesek);  // a 'fel' - 'le' adatok cseréje az irányváltás miatt
    }
    // 12 * 5 * 140    5 * 140 * 82
    elseif($start > $touch1 && $touch1 < $touch2 || $start != 1 && $start < $touch1 && $touch2 < $touch1)
    {
        $statement1 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN 2 AND ? ORDER BY id DESC');
        $statement1->execute([$start]);
        $telepulesek1 = $statement1->fetchAll(PDO::FETCH_ASSOC);

        $statement2 = $pdo->prepare('SELECT * FROM fooldal WHERE id BETWEEN ? AND 148 ORDER BY id DESC');
        $statement2->execute([$start]);
        $telepulesek2 = $statement2->fetchAll(PDO::FETCH_ASSOC);

        $telepulesek3 = array_merge($telepulesek1, $telepulesek2);

        $telepulesek = dataSlider($telepulesek3);
        $telepulesek = dataChange($telepulesek);  // a 'fel' - 'le' adatok cseréje az irányváltás miatt
    }
    // $start == 1 tehát szegedi indulás kelet felé
    else{
        $telepulesek = $osszesTelepules;
    }

    return $telepulesek;
}

function routeListKmHandler()
{
    /*$start = $_POST["startId"];
    $touch1 = $_POST["touchId1"];
    $touch2 = $_POST["touchId2"];
    $km = $_POST["km"];
    var_dump($start, $touch1, $touch2, $km);*/


    session_start();
    //$_SESSION['userId'] = $user['id']; 
    $_SESSION['startId'] = $_GET["startId"];
    $_SESSION['touchId1'] = $_GET["touchId1"];
    $_SESSION['touchId2'] = $_GET["touchId2"];
    $_SESSION['km'] = $_GET["km"];

    $startId = $_GET["startId"];
    $touchId1 = $_GET["touchId1"];
    $touchId2 = $_GET["touchId2"];
    $km = $_GET["km"];
    
    $telepulesek = routeByKmDatas($startId, $touchId1, $touchId2, $km);

    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routeListKm.phtml',[
            //'osszesTelepules' => $osszesTelepules,
            'telepulesek' => $telepulesek,
            'km' => $km,
            'startId' => $startId,
            'touchId1' => $touchId1,
            'touchId2' => $touchId2
        ])
        //'isAuthorized' => isLoggedIn() //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}

function routesByKmPdfHandler()
{
    
    session_start();

    if (!isset($_SESSION['startId']) && !isset($_GET["startId"]))
    { 
        $startId = 4; //$_GET["startId"];
        $touchId1 = 2; //$_GET["touchId1"];
        $touchId2 = 133; //$_GET["touchId2"];
        $km = 120; //$_GET["km"];
        var_dump('++++++++++++   Nincs session  routesByKmPdfHandler !!!!!!!!!!!!!!!!!');
    }
    else{
        $_SESSION['startId'] = $_GET["startId"];
        $_SESSION['touchId1'] = $_GET["touchId1"];
        $_SESSION['touchId2'] = $_GET["touchId2"];
        $_SESSION['km'] = $_GET["km"];

        $startId = $_GET["startId"];
        $touchId1 = $_GET["touchId1"];
        $touchId2 = $_GET["touchId2"];
        $km = $_GET["km"];
        var_dump('Van session  routesByKmPdfHandler  !!!!!!!!!!!!!!!!!');
    }
    
    
    $telepulesek = routeByKmDatas($startId, $touchId1, $touchId2, $km);
    
    echo compileTemplate('PdfWrapper.phtml', [
        'content' => compileTemplate('routeByKmPdfSite.phtml',[
            'telepulesek' => $telepulesek,
            'km' => $km,
            'startId' => $startId,
            'touchId1' => $touchId1,
            'touchId2' => $touchId2
            //'osszesTelepules' => $osszesTelepules,
        ])

        //'isAuthorized' => isLoggedIn() //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}

function routeByKmToPdfHandler()
{
    session_start();
    /*$_SESSION['startId'] = $_GET["startId"];
    $_SESSION['touchId1'] = $_GET["touchId1"];
    $_SESSION['touchId2'] = $_GET["touchId2"];
    $_SESSION['km'] = $_GET["km"];

    $startId = $_GET["startId"];
    $touchId1 = $_GET["touchId1"];
    $touchId2 = $_GET["touchId2"];
    $km = $_GET["km"];*/

    $dompdf = new Dompdf();

    $dompdf->set_option('enable_remote', TRUE);
    $dompdf->loadHtmlFile('http://localhost/Bringatura_MKK/routesByKmPdf');//genRoutePdf
    //$html = file_get_contents('views/test.phtml');
    //$dompdf->loadHtml($html);
    
    $dompdf->setPaper('A4', 'landscape');
    //$dompdf->setFont('Arial', 'ital', 8);
    // Render the HTML as PDF
    $dompdf->render();
    
    // Output the generated PDF to Browser
    $dompdf->stream('negyedik.pdf', array("Attachment"=>0));


}

?>