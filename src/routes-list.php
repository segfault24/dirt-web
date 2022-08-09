<?php
use Dirt\Tools;

// get user lists
$app->get('/my-lists', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    // retrieve all the user's lists
    $db = Dirt\Database::getDb();
    $sql = 'SELECT dl.listId, dl.userId, dl.name, dl.public
            FROM dirtlist AS dl
            WHERE userId=:userid
            GROUP BY dl.listId;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':userid' => $u->getUserId()
    ));

    $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $args['data'] = $lists;

    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'my-lists.phtml', $args);
});

// helper function, adds item to items array, increasing existing
// quantity if the item already exists in the arry
function add_item(&$items, $typename, $quantity) {
    if (array_key_exists($typename, $items)) {
        $items[$typename] = $items[$typename] + intval($quantity);
    } else {
        $items[$typename] = intval($quantity);
    }
}

// parse text input into a 2d array of typenames and quantities
function parseInput($input) {
    $lines = explode("\n", $input);
    $items = array();
    $errors = array();

    // filter empty lines
    $lines2 = array();
    for ($i=0; $i<count($lines); $i++) {
        $line = trim($lines[$i]);
        if (strlen($line)==0) {
            continue;
        }
        array_push($lines2, $line);
    }

    if (count($lines2)>0) {
        if (substr($lines2[0], 0, 1) === "[") {
            // eft formatted
            $typename = substr($lines2[0], 1, strpos($lines2[0], ",")-1);
            add_item($items, $typename, 1);
            for ($i=1; $i<count($lines2); $i++) {
                // filter stuff like "[Empty Low slot]"
                if (substr($lines2[$i], 0, 6) === "[Empty") {
                    continue;
                }
                // check if it's a cargo item
                //   "{typename} x{quantity}"
                $parts = explode(" ", $lines2[$i]);
                $last = $parts[count($parts)-1];
                if (substr($last, 0, 1) === "x") {
                    $typename = implode(" ", array_slice($parts, 0, count($parts)-1));
                    $quantity = intval(substr($last, 1, strlen($last)-1));
                    add_item($items, $typename, $quantity);
                    continue;
                }
                // probably mod slot
                //   "{typename}[, {ammo_type}]"
                $idx = strpos($lines2[$i], ",");
                if ($idx) {
                    $typename = substr($lines2[$i], 0, $idx);
                } else {
                    $typename = $lines2[$i];
                }
                add_item($items, $typename, 1);
            }
        } else {
            // probably contract or inventory formatted
            //   "{typename}[\t{quantity}]"
            for ($i=0; $i<count($lines2); $i++) {
                $parts = explode("\t", $lines2[$i]);
                $typename = $parts[0];
                if (count($parts)==1) {
                    $quantity = 1;
                } else {
                    $qt = intval(str_replace(',', '', $parts[1]));
                    if ($qt!=0) {
                        $quantity = $qt;
                    } else {
                        array_push($errors, "bad line '".$lines2[$i]."'");
                        continue;
                    }
                }
                add_item($items, $typename, $quantity);
            }
        }
    }

    $result = array(
        "items" => $items,
        "errors" => $errors
    );
    return $result;
}

// create a new list
$app->post('/my-lists', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }

    // set default parameters if necessary
    $listname = $request->getParsedBody()['list-add-name'];
    if ($listname == '') {
        $listname = '_list_' . uniqid();
    }
    //$public = $request->getParsedBody()['info']['public'];
    //if ($public == '') {
        $public = 0;
    //}

    // create the new list
    $db = Dirt\Database::getDb();
    $sql = 'INSERT INTO dirtlist (name, userId, public) VALUES (:listname, :userid, :public);';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listname' => $listname,
        ':userid' => $u->getUserId(),
        ':public' => $public
    ));

    // get the id of the list we just inserted
    $sql = 'SELECT LAST_INSERT_ID()';
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $listid = $stmt->fetchAll(PDO::FETCH_ASSOC)[0]['LAST_INSERT_ID()'];

    // parse the body and insert the iteams
    $sql = 'INSERT INTO dirtlistitem (listId, typeId, quantity) VALUES (:listid, (SELECT typeId FROM invtype WHERE typeName=:typename), :quantity)';
    $stmt = $db->prepare($sql);
    $result = parseInput($request->getParsedBody()['list-add-input']);
    $items = $result['items'];
    $keys = array_keys($items);
    for ($i=0; $i<count($keys); $i++) {
        $stmt->execute(array(
            ':listid' => $listid,
            ':typename' => $keys[$i],
            ':quantity' => $items[$keys[$i]]
        ));
    }

    return $response->withStatus(302)->withHeader('Location', '/list/'.$listid);
});

// get specific user list
$app->get('/list/{listid}', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    // retrieve the list's info
    $db = Dirt\Database::getDb();
    $sql = 'SELECT listId, userId, name, public FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // 404 not found if the list doesn't exist
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    $listinfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 403 forbidden if the user doesn't own the list and it's not public
    if ($listinfo['public'] != 1 && $listinfo['userId'] != $u->getUserId()) {
        return $response->withStatus(403);
    }

    // retrieve the items in the list
    $sql = 'SELECT li.typeId, i.typeName, li.quantity FROM dirtlistitem AS li JOIN invtype AS i ON li.typeId=i.typeId WHERE li.listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    $listitems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $args['listinfo'] = $listinfo;
    $args['listitems'] = $listitems;
    $response = $this->cache->denyCache($response);
    return $this->renderer->render($response, 'list.phtml', $args);
});

// delete specific user list
$app->post('/list/{listid}/delete-list', function ($request, $response, $args) {
    $u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }
    $u->setTemplateVars($args);

    // retrieve the list's info
    $db = Dirt\Database::getDb();
    $sql = 'SELECT listId, userId FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // 404 not found if the list doesn't exist
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    $listinfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 403 forbidden if the user doesn't own the list
    if ($listinfo['userId'] != $u->getUserId()) {
        return $response->withStatus(403);
    }

    // remove listitems
    $sql = 'DELETE FROM dirtlistitem WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // remove list
    $sql = 'DELETE FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    return $response->withStatus(302)->withHeader('Location', '/my-lists');
});

// delete specific item on user list
$app->post('/list/{listid}/delete-item/{typeid}', function ($request, $response, $args) {
$u = Dirt\User::getUser();
    if (! $u->isLoggedIn()) {
        return $response->withStatus(302)
            ->withHeader('Location', '/login');
    }

    // retrieve the list's info
    $db = Dirt\Database::getDb();
    $sql = 'SELECT listId, userId, public FROM dirtlist WHERE listId=:listid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid']
    ));

    // 404 not found if the list doesn't exist
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    $listinfo = $stmt->fetch(PDO::FETCH_ASSOC);

    // 403 forbidden if the user doesn't own the list
    if ($listinfo['userId'] != $u->getUserId()) {
        return $response->withStatus(403);
    }

    // retrieve the item from the list
    $sql = 'SELECT typeId FROM dirtlistitem WHERE listId=:listid AND typeId=:typeid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid'],
        ':typeid' => $args['typeid']
    ));

    // 404 not found if the item isn't in the list
    if ($stmt->rowCount() == 0) {
        return $response->withStatus(404);
    }

    // remove the listitem
    $sql = 'DELETE FROM dirtlistitem WHERE listId=:listid AND typeId=:typeid;';
    $stmt = $db->prepare($sql);
    $stmt->execute(array(
        ':listid' => $args['listid'],
        ':typeid' => $args['typeid']
    ));

    return $response->withStatus(302)->withHeader('Location', '/list/'.$args['listid']);
});

