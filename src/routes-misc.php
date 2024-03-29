<?php
use Dirt\Tools;

$app->get('/', function ($request, $response, $args) {
    return $response->withStatus(302)->withHeader('Location', '/login');
});

// //////////////////////////////////////////////
// // General Pages ////
// //////////////////////////////////////////////

$app->get('/dashboard', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    return $this->renderer->render($response, 'dashboard.phtml', $args);
});

$app->get('/search', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    $q = '%' . $request->getQueryParams()['q'] . '%';
    $db = Dirt\Database::getDb();
    $sql = 'SELECT `typeId`, `typeName`
            FROM invtype
            WHERE `typeName` LIKE :query
            AND `published`=1
            ORDER BY `typeName`
            LIMIT 100';
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':query', $q);
    $stmt->execute();

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $cnt = count($rows);
    if ($cnt >= 100) {
        $cnt = '100+';
    }

    if ($cnt == 1) {
        return $response->withStatus(302)
            ->withHeader('Location', '/browse?type=' . htmlspecialchars($rows[0]['typeId']));
    } else {
        $args['query'] = $request->getQueryParams()['q'];
        $args['count'] = $cnt;
        $args['data'] = $rows;
        return $this->renderer->render($response, 'search.phtml', $args);
    }
});

$app->get('/wallet', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    return $this->renderer->render($response, 'wallet.phtml', $args);
});

$app->get('/economic-reports', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    return $this->renderer->render($response, 'mer.phtml', $args);
});

