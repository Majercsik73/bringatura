<?php
require_once "views/vendor/autoload.php";

function render($path, $params = [])  // A "submitMessageHandler"- ben lévő "render"-hez kell!
{
    ob_start();
    require __DIR__ . '/views/' . $path;
    return ob_get_clean();
}

function contactHandler(){

    session_start();
    
    echo compileTemplate('wrapper.phtml', [
        'content' => compileTemplate('contactForm.phtml',[
            'info' => $_GET['info'] ?? '',
            'userId' => $_SESSION['userId'] ?? '',
            'userName' => $_SESSION['userName'] ?? '',
            'isAuthorized' => isLoggedIn()//megvizsgáljuk, hogy be van-e jelentkezve
        ]),
        'isAuthorized' => isLoggedIn(),//megvizsgáljuk, hogy be van-e jelentkezve -->ezt a wrapperbe küldjük!!
    ]);
}

function submitMessageHandler()
{
    $pdo = getConnection();
    $statement = $pdo->prepare("INSERT INTO `messages` 
    (`email`, `subject`, `body`, `status`, `numberOfAttempts`, `createdAt`) 
    VALUES 
    (?, ?, ?, ?, ?, ?);");

    $body = render("email-template.phtml", [
        'name' =>  $_POST['name'] ?? '',
        'email' =>  $_POST['email'] ?? '',
        'content' =>  $_POST['content'],
    ]);

    $statement->execute([
        "uzenet@turazzketkereken.hu",//$_SERVER['RECIPIENT_EMAIL'],
        "Új üzenet érkezett",
        $body,
        'notSent',
        0,
        time()
    ]);

    header('Location: ' . getPathWithId($_SERVER['HTTP_REFERER']) . '?&info=küldesSikeres');  
}

function sendMailsHandler()
{
    // header('Content-Type: application/json');
    // if (($_POST['key'] ?? 0) !== ($_SERVER['MASTER_PASSWORD'] ?? 1)) {
    //     http_response_code(401);
    //     echo json_encode(['error' => 'unauthorized']);
    //     return;
    // }
    $pdo = getConnection();
    $statement = $pdo->prepare(
        "SELECT * FROM messages 
        WHERE 
        status = 'notSent' AND 
        numberOfAttempts < 30 
        ORDER BY createdAt ASC"
    );

    $statement->execute();
    $messages = $statement->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as $message) {
        $pdo = getConnection();
        $statement = $pdo->prepare(
            "UPDATE `messages` SET 
                status = 'sending', 
                numberOfAttempts = ? 
            WHERE id = ?;"
        );

        $statement->execute([
            (int)$message['numberOfAttempts'] + 1,
            $message['id']
        ]);

        $isSent = sendMail(
            $message['email'],
            $message['subject'],
            $message['body']
        );

        if ($isSent) {
            $statement = $pdo->prepare(
                "UPDATE `messages` SET status = 'sent', sentAt = ? WHERE id = ?;"
            );
            $statement->execute([
                time(),
                $message['id'],
            ]);
        } else {
            $statement = $pdo->prepare("UPDATE `messages` SET status = 'notSent' WHERE id = ?;");
            $statement->execute([
                $message['id']
            ]);
        }
    }
}

