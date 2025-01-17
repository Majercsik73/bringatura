<?php

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once "basicGlobalRoutes.php";
require_once "displayRouteList.php";
require_once "globalRoutesByKm.php";
require_once "contact.php";
require_once "views/vendor/autoload.php";
use Dompdf\Dompdf;

/*
INSERT INTO `users` (`id`, `email`, `password`, `createdAt`) VALUES (NULL, 'peldaEmail', 'peldaJelszo', '123');
*/
// útvonalválasztó
// https://kodbazis.hu/php-az-alapoktol/termek-listazo-website

$method = $_SERVER["REQUEST_METHOD"];
$parsed = parse_url($_SERVER['REQUEST_URI']);
$path = $parsed['path'];

$routes = [
    "GET" => [
        "/Bringatura_MKK/" => "homeHandler",         // "/Bringatura_MKK/" => "homeHandler",
        "/Bringatura_MKK/tervezo" => "plannerHandler",
        "/Bringatura_MKK/varosok-megtekintese" => "cityListHandler",  //countryListHandler
        "/Bringatura_MKK/utvonal-valaszto" => "routeSelectHandler",
        "/Bringatura_MKK/utvonal-valaszto-km" => "routeSelectByKmHandler",
        '/Bringatura_MKK/utvonal' => 'routeListHandler',
        '/Bringatura_MKK/utvonal-save' => 'updateRoutePlanningHandler',
        '/Bringatura_MKK/utvonal-saved' => "savedRouteHandler",
        '/Bringatura_MKK/utvonal-km' => 'routeListKmHandler',
        '/Bringatura_MKK/utvonal-km-save' => 'updatePlanningByKmHandler',
        '/Bringatura_MKK/utvonal-km-saved' => 'savedRouteKmHandler',
        '/Bringatura_MKK/generatePdf' => 'htmlToPdfHandler',
        '/Bringatura_MKK/listAllPdf' => 'generatePdfHandler',
        '/Bringatura_MKK/routeListPdf' => 'generateRouteListToPdfHandler',
        '/Bringatura_MKK/routesByKmPdf' => 'routesByKmPdfHandler',
        '/Bringatura_MKK/loginAndRegister' => 'loginAndRegisterHandler',
        '/Bringatura_MKK/contact' => 'contactHandler'
    ],  
    "POST" => [
        '/Bringatura_MKK/genPdf' => 'htmlToPdfHandler',
        '/Bringatura_MKK/genRoutePdf' => 'routeListToPdfHandler',
        '/Bringatura_MKK/genByKmPdf' => 'routeByKmToPdfHandler',
        '/Bringatura_MKK/register' => 'registrationHandler',
        '/Bringatura_MKK/login' => 'loginHandler',
        '/Bringatura_MKK/logout' => 'logoutHandler',
        '/Bringatura_MKK/submit-message' => 'submitMessageHandler',
        '/Bringatura_MKK/send-mails' => 'sendMailsHandler'

    ],
];

$handlerFunction = $routes[$method][$path] ?? "notFoundHandler"; 

$handlerFunction();

function plannerHandler()
{
    session_start();
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routePlanner.phtml',[]),
        'isAuthorized' => isLoggedIn(), //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}

function getPathWithId($url)
{ 
    $parsed = parse_url($url);
    if(!isset($parsed['query'])) {
        return $url;
    }

    /*echo "<pre>";
    var_dump($parsed);*/

    $queryParams = []; 
    parse_str($parsed['query'], $queryParams);
    //var_dump($queryParams);
    //var_dump($parsed['path'] . "?id=" . $queryParams['id']);
    return $parsed['path']; // . "?id=" . $queryParams['id'];
}


function registrationHandler()
{
    if(isset($_POST["name"]) && !empty($_POST["name"]) && 
        isset($_POST["email"]) && !empty($_POST["email"]) &&
        isset($_POST["password_1"]) && !empty($_POST["password_2"]) && 
        isset($_POST["password_2"]) && !empty($_POST["password_2"]))
    {
        // A test_input fügvénnyel megtisztítjuk a postolt adatokat a kártékony scriptektől
        $name = test_input($_POST["name"]);  //test_input a 198. oldaltól
        $email = test_input($_POST["email"]);
        $pw1 = test_input($_POST["password_1"]);
        $pw2 = test_input($_POST["password_2"]);
        
        //$email = $_POST["email"];

        if(!emailCheck($email)) //emailCheck a 183. sortól
        {
            header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=existingEmail');
            return;
        }
        // Megvizsgáljuk, hogy a jelszavek megegyeznek-e
        if($pw1 != $pw2){
            header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=pwError');
            return;
        }
        // Ha minden rendben van elindítjuk az adatbázisba való betöltést
        $pdo = getConnection();
        $statment = $pdo->prepare(
            "INSERT INTO `users` (`name`, `email`, `password`, `createdAt`)  
            VALUES (?, ?, ?, ?);"
        );
        $statment->execute([
            $name, //$_POST["name"],
            $email, //$_POST["email"],
            password_hash($pw1, PASSWORD_DEFAULT), //$_POST["password"]
            time()
        ]);
        // az adatbázisban beállítjuk a kezdőértékeket mindkét elmentett útvonalhoz
        insertNewUserPlanningDatas();

        //Email küldése regisztrációkor
        $to = $email; //"somebody@example.com";
        $subject = "My subject";
        $txt = "Hello world!";
        $headers = "From: webmaster@example.com" . "\r\n" .
                "CC: somebodyelse@example.com";

        mail($to,$subject,$txt,$headers);

        // töröljük a globális változót
        unset($_POST);
        
// A következő minden "header('Location:...)"-ben az '&info' elé azért kell a '?'(kérdőjel), mert
//a getPathWithId levágja az eredetileg ott lévő '?'-et!!!
        header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=registrationSuccessful');
    }

    else header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=noData');
}

function insertNewUserPlanningDatas()
{
    $pdo = getConnection();
    $statement = $pdo->prepare('INSERT INTO `datas` (`start`, `touching`, `end`) VALUES (?, ?, ?)');
    $statement ->execute([1, 2, 3]);

    $statement2 = $pdo->prepare('INSERT INTO `datasbykm` (`startId`,`touchId1`,`touchId2`, `km`) VALUES (?, ?, ?, ?)');
    $statement2 ->execute([1, 2, 3, 60]);
    
}


function loginHandler()
{
    $pdo = getConnection();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $statement->execute([$_POST["email"]]);
    
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    
    if(!$user) {
        header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=invalidCredentials');
        return;
    }

    $isVerified = password_verify($_POST['password'], $user["password"]);//$_POST['password'] != $user["password"]
    
    if(!$isVerified) {
        header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=invalidCredentials');
        return;
    }
    
    session_start();
    $_SESSION['userId'] = $user['id'];
    $_SESSION['userName'] = $user['name']; 
    header('Location: '. getPathWithId($_SERVER['HTTP_REFERER']));  //  /Bringatura_MKK/utvonal-valaszto
    
}

function emailCheck($email): bool
{
    $pdo = getConnection();
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $statement->execute([$email]);

    $user = $statement->fetch(PDO::FETCH_ASSOC);

    if($user) {
        return false;
    }

    return true;
}

function test_input($data) 
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
  }

function isLoggedIn(): bool
{
    if (!isset($_COOKIE[session_name()])) { 
        return false; 
    }

    //session_start();

    if (!isset($_SESSION['userId'])) { 
        return false; 
    }

    return true;
}

//kijelentkezés
function logoutHandler()
{
    
    session_start();  //session-t indítunk
    $params = session_get_cookie_params();  //kiszedjük a cookie paramétereket
    //beállítjuk a böngészőből törölni kívánt cookie paramétereit: az első a neve, 2. tartalma (üres string), 
    //3. az ideje, a többi a kiszedett paraméterek:
    setcookie(session_name(),  '', 0, $params['path'], $params['domain'], $params['secure'], isset($params['httponly']));
    //miután töröltük a böngészőből, szerver oldalon is töröljük a munkamenetet:
    session_destroy();// Ne ragadjonak be az adatok!!!!
    header('Location: /Bringatura_MKK/'); //.getPathWithId($_SERVER['HTTP_REFERER'])

}

// A különböző lekérdezésekkor kapott adatok csúsztatása a megjelenítéshez
function dataSlider($telepulesek)
{
    $hossz = count($telepulesek);
        for ($i = $hossz-1; $i > -1 ; $i--)
        {
            if ($i == 0)
            {
                $telepulesek[$i]['tav'] = 0;
                $telepulesek[$i]['utszam'] = '----';
                $telepulesek[$i]['fel'] = 0;
                $telepulesek[$i]['le'] = 0;
            }
            else
            {
                $telepulesek[$i]['utszam'] = $telepulesek[$i-1]['utszam'];
                $telepulesek[$i]['tav'] = $telepulesek[$i-1]['tav'];
                $telepulesek[$i]['fel'] = $telepulesek[$i-1]['fel'];
                $telepulesek[$i]['le'] = $telepulesek[$i-1]['le'];
            }
        }
    return $telepulesek;
}

//ellentétes irányban a 'fel' - 'le' értékeket fel kell cserélni
function dataChange($telepulesek) 
{
    $hossz = count($telepulesek);
        for ($i = 1; $i < $hossz ; $i++)
        {
            $c = $telepulesek[$i]['fel'];
            $telepulesek[$i]['fel'] = $telepulesek[$i]['le'];
            $telepulesek[$i]['le'] = $c;
        }
    return $telepulesek;
}

//Útvonalszakasz meghatározásához "utvonal-valaszto" url-ről, a fejléc tervezőjéből
function routeSelectHandler()
{
    session_start();
    if(!isLoggedIn()) {
        
        //header('Location: /Bringatura_MKK/loginAndRegister'); 
        echo compileTemplate("wrapper.phtml", [
            'content' => compileTemplate('subscriptionForm.phtml', [
                'info' => $_GET['info'] ?? '',
                'isRegistration' => isset($_GET['isRegistration']),
                'url' => getPathWithId($_SERVER['REQUEST_URI']),
            ]),
            'isAuthorized' => false, //// nincs bejelentkezve -->ezt küldjük a wrapperbe
        ]);
        return;
    }

    $pdo = getConnection();

    $statement = $pdo->prepare('SELECT * FROM fooldal');
    $statement->execute();
    $telepulesek = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    /*echo"<pre>";
    var_dump($telepulesek);*/
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routeSelect.phtml',[
            'telepulesek' => $telepulesek,
            'userId' => $_SESSION['userId'] ?? '',
            'userName' => $_SESSION['userName'] ?? '',
            'isAuthorized' => isLoggedIn()//megvizsgáljuk, hogy be van-e jelentkezve
        ]),
        'isAuthorized' => isLoggedIn(),//megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);

}

//Útvonal meghatározáshoz km alapján "utvonal-valaszto-km" url-ről, a fejléc tervezőjéből
function routeSelectByKmHandler()
{
    session_start();
    if(!isLoggedIn()) {
        
        //header('Location: /Bringatura_MKK/loginAndRegister'); 
        echo compileTemplate("wrapper.phtml", [
            'content' => compileTemplate('subscriptionForm.phtml', [
                'info' => $_GET['info'] ?? '',
                'isRegistration' => isset($_GET['isRegistration']),
                'url' => getPathWithId($_SERVER['REQUEST_URI']),
            ]),
            'isAuthorized' => false, //// nincs bejelentkezve -->ezt küldjük a wrapperbe
        ]);
        return;
    }

    $pdo = getConnection();

    $statement = $pdo->prepare('SELECT * FROM fooldal');
    $statement->execute();
    $telepulesek = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    /*echo"<pre>";
    var_dump($telepulesek);*/
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('routeSelectByKm.phtml',[
            'telepulesek' => $telepulesek,
            'userId' => $_SESSION['userId'] ?? '',
            'userName' => $_SESSION['userName'] ?? '',
            'isAuthorized' => isLoggedIn()
        ]),
        'isAuthorized' => isLoggedIn(), //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}

function homeHandler()
{
    session_start();
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('home.phtml',[
            'userId' => $_SESSION['userId'] ?? '',
            'userName' => $_SESSION['userName'] ?? '',
            'isAuthorized' => isLoggedIn()
        ]),
        'isAuthorized' => isLoggedIn(), //megvizsgáljuk, hogy be van-e jelentkezve -->ezt küldjük a wrapperbe
    ]);
}

function getConnection()
{
    return new PDO(
        'mysql:host='.$_SERVER['DB_HOST'].';dbname='.$_SERVER['DB_NAME'],
        $_SERVER['DB_USER'],
        $_SERVER['DB_PASSWORD']
    );
}


function compileTemplate($filePath, $params =[]): string
{
    ob_start();
    require __DIR__."/views/".$filePath;
    return ob_get_clean();
}

function notFoundHandler()
{
    echo "Oldal nem található";
}

?>