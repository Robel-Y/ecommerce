<?php
/* ============================================
   CART PERSISTENCE - Procedural
   Persist logged-in users' carts in DB
============================================ */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/utilities.php';

function cart_session_ensure()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => [],
            'total' => 0,
            'count' => 0
        ];
    }

    if (!isset($_SESSION['cart']['items']) || !is_array($_SESSION['cart']['items'])) {
        $_SESSION['cart']['items'] = [];
    }

    if (!isset($_SESSION['cart']['total'])) {
        $_SESSION['cart']['total'] = 0;
    }

    if (!isset($_SESSION['cart']['count'])) {
        $_SESSION['cart']['count'] = 0;
    }
}

function cart_session_recalculate_totals()
{
    cart_session_ensure();

    $total = 0;
    $count = 0;

    foreach ($_SESSION['cart']['items'] as $item) {
        $price = (float) ($item['price'] ?? 0);
        $qty = (int) ($item['quantity'] ?? 0);
        if ($qty <= 0) {
            continue;
        }
        $total += ($price * $qty);
        $count += $qty;
    }

    $_SESSION['cart']['total'] = $total;
    $_SESSION['cart']['count'] = $count;
    $_SESSION['cart_count'] = $count;
}

function cart_db_get_items($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return [];
    }

    $sql = "SELECT c.product_id AS id,
                   c.quantity,
                   p.name,
                   p.price,
                   p.image AS image_url,
                   p.stock
            FROM cart c
            INNER JOIN products p ON p.id = c.product_id
            WHERE c.user_id = :uid
            ORDER BY c.updated_at DESC";

    if (!db_query($sql)) {
        return [];
    }

    db_bind(':uid', $user_id);
    $rows = db_result_set();

    $items = [];
    foreach ($rows as $row) {
        $items[] = [
            'id' => (int) ($row['id'] ?? 0),
            'name' => (string) ($row['name'] ?? ''),
            'price' => (float) ($row['price'] ?? 0),
            'image' => (string) ($row['image_url'] ?? ''),
            'quantity' => (int) ($row['quantity'] ?? 0),
            'stock' => (int) ($row['stock'] ?? 0),
        ];
    }

    return $items;
}

function cart_load_db_to_session($user_id)
{
    cart_session_ensure();

    $items = cart_db_get_items($user_id);
    $_SESSION['cart']['items'] = $items;
    cart_session_recalculate_totals();

    $_SESSION['cart_loaded_from_db'] = true;
}

function cart_db_replace_from_session($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }

    cart_session_ensure();

    db_begin_transaction();
    try {
        db_query('DELETE FROM cart WHERE user_id = :uid');
        db_bind(':uid', $user_id);
        db_execute();

        if (!empty($_SESSION['cart']['items'])) {
            db_query('INSERT INTO cart (user_id, product_id, quantity) VALUES (:uid, :pid, :qty)');
            foreach ($_SESSION['cart']['items'] as $item) {
                $product_id = (int) ($item['id'] ?? 0);
                $quantity = (int) ($item['quantity'] ?? 0);

                if ($product_id <= 0 || $quantity <= 0) {
                    continue;
                }

                db_bind(':uid', $user_id);
                db_bind(':pid', $product_id);
                db_bind(':qty', $quantity);
                db_execute();
            }
        }

        db_commit();
        return true;
    } catch (Throwable $e) {
        db_rollback();
        return false;
    }
}

function cart_db_clear($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return false;
    }

    db_query('DELETE FROM cart WHERE user_id = :uid');
    db_bind(':uid', $user_id);
    return db_execute();
}

function cart_merge_session_into_db_on_login($user_id)
{
    $user_id = (int) $user_id;
    if ($user_id <= 0) {
        return;
    }

    cart_session_ensure();

    $db_items = cart_db_get_items($user_id);
    $merged = [];

    // Seed with DB items
    foreach ($db_items as $item) {
        $pid = (int) ($item['id'] ?? 0);
        if ($pid <= 0) {
            continue;
        }
        $merged[$pid] = $item;
    }

    // Merge session items (guest cart before login)
    foreach ($_SESSION['cart']['items'] as $item) {
        $pid = (int) ($item['id'] ?? 0);
        $qty = (int) ($item['quantity'] ?? 0);
        if ($pid <= 0 || $qty <= 0) {
            continue;
        }

        $product = get_product_by_id($pid);
        if (!$product) {
            continue;
        }

        $stock = (int) ($product['stock'] ?? 0);
        $base_qty = isset($merged[$pid]) ? (int) ($merged[$pid]['quantity'] ?? 0) : 0;
        $new_qty = $base_qty + $qty;
        if ($stock > 0) {
            $new_qty = min($new_qty, $stock);
        }

        $merged[$pid] = [
            'id' => $pid,
            'name' => (string) ($product['name'] ?? ''),
            'price' => (float) ($product['price'] ?? 0),
            'image' => (string) ($product['image_url'] ?? ''),
            'quantity' => $new_qty,
            'stock' => $stock,
        ];
    }

    $_SESSION['cart']['items'] = array_values($merged);
    cart_session_recalculate_totals();
    cart_db_replace_from_session($user_id);

    $_SESSION['cart_loaded_from_db'] = true;
}
