<?php
require_once "views/vendor/autoload.php";

use Dompdf\Dompdf;


function routeListDatas($start, $touching, $end)
{
    /*var_dump($_GET["start"]);
    echo("   *****   ");
    var_dump($_GET["touching"]);
    echo("   *****   ");
    var_dump($_GET["end"]);*/
    /*$start = $GET["start"];
    $touching = $GET["touching"];
    $end = $GET["end"];*/

    $pdo = getConnection();
    /*
    $statement = $pdo->prepare('SELECT * FROM fooldal');
    $statement->execute();
    $osszesTelepules = $statement->fetchAll(PDO::FETCH_ASSOC);*/
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
    // 142 * 146 * 5 
    elseif ($start < $touching && $touching > $end && $start > $end)
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

function routeListHandler()
{
    /*var_dump($_GET["start"]);
    echo("   *****   ");
    var_dump($_GET["touching"]);
    echo("   *****   ");
    var_dump($_GET["end"]);*/
    session_start();
    //$_SESSION['userId'] = $user['id']; 
    $_SESSION['start'] = $_GET["start"];
    $_SESSION['touching'] = $_GET["touching"];
    $_SESSION['end'] = $_GET["end"];

    $start = $_GET["start"];
    $touching = $_GET["touching"];
    $end = $_GET["end"];
    
    $telepulesek = routeListDatas($start, $touching, $end);
    //echo"<pre>";
    //var_dump($telepulesek);
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routeList.phtml',[
            'telepulesek' => $telepulesek,
            'start' => $start,
            'touching' => $touching,
            'end' => $end
            //'osszesTelepules' => $osszesTelepules,
        ])
        //'isAuthorized' => isLoggedIn() //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}


function generateRouteListToPdfHandler()
{
    /*var_dum= $GET["start"]);
    echo("   *****   ");
    var_dum= $GET["touching"]);
    echo("   *****   ");
    var_dum= $GET["end"]);
    
    $telepulesek = routeListDatas($start, $touching, $end);*/
    session_start();

    if (!isset($_SESSION['start']))
    { 
        $start = $_GET["start"];
        $touching = $_GET["touching"];
        $end = $_GET["end"]; 
        var_dump('++++++++++++   Nincs session  generateRouteListToPdfHandler !!!!!!!!!!!!!!!!!');
    }
    else{
        $start = $_SESSION["start"];
        $touching = $_SESSION["touching"];
        $end = $_SESSION["end"];
        var_dump('Van session  generateRouteListToPdfHandler  !!!!!!!!!!!!!!!!!');
    }
    
    
    $telepulesek = routeListDatas($start, $touching, $end);
    
    echo compileTemplate('PdfWrapper.phtml', [
        'content' => compileTemplate('routePdfSite.phtml',[
            'telepulesek' => $telepulesek,
            'start' => $start,
            'touching' => $touching,
            'end' => $end
            //'osszesTelepules' => $osszesTelepules,
        ])

        //'isAuthorized' => isLoggedIn() //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}


function routeListToPdfHandler()
{
    session_start();
/*
    if (!isset($_SESSION['start']) && !isset($_GET["start"]))
    { 
        $start = $_GET["start"];
        $touching = $_GET["touching"];
        $end = $_GET["end"]; 
        var_dump('++++++++++++   Nincs session  routeListPdfHandler !!!!!!!!!!!!!!!!!');
    }
    else{
        $start = $_SESSION["start"];
        $touching = $_SESSION["touching"];
        $end = $_SESSION["end"];
        var_dump('Van session  routeListPdfHandler  !!!!!!!!!!!!!!!!!');
    }
*/
    $dompdf = new Dompdf();

    $dompdf->set_option('enable_remote', TRUE);
    $dompdf->loadHtmlFile('http://localhost/Bringatura_MKK/routeListPdf?');//genRoutePdf
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