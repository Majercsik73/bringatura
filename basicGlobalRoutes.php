<?php

require_once "views/vendor/autoload.php";
use Dompdf\Dompdf;

function cityListHandler()
{
    $pdo = getConnection();

    $statement = $pdo->prepare('SELECT * FROM fooldal');
    $statement->execute();
    $telepulesek = $statement->fetchAll(PDO::FETCH_ASSOC);

    
    
    /*echo"<pre>";
    var_dump($telepulesek);*/
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('cityList.phtml',[
            'telepulesek' => $telepulesek,
        ])
        //'isAuthorized' => isLoggedIn() //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
    
    //generatePdfHandler($telepulesek);
    
}

function htmlToPdfHandler()
{
    $dompdf = new Dompdf();

    $dompdf->set_option('enable_remote', TRUE);
    $dompdf->loadHtmlFile('http://localhost/Bringatura_MKK/generatePdf?');
    //$html = file_get_contents('http://localhost/Bringatura_MKK/varosok-megtekintese?');
    //$dompdf->loadHtml($html);
    
    $dompdf->setPaper('A4', 'landscape');
    //$dompdf->setFont('Arial', 'ital', 8);
    // Render the HTML as PDF
    $dompdf->render();
    
    // Output the generated PDF to Browser
    $dompdf->stream('harmadik.pdf');
}

function generatePdfHandler()
{
    
    $pdo = getConnection();

    $statement = $pdo->prepare('SELECT * FROM fooldal');
    $statement->execute();
    $telepulesek = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    /*echo"<pre>";
    var_dump($telepulesek);*/
    echo compileTemplate('Pdf.phtml', [
        'content' => compileTemplate('pdfSite.phtml',[
            'telepulesek' => $telepulesek,
        ])
        //'isAuthorized' => isLoggedIn() //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
    //htmlToPdfHandler();
}

?>