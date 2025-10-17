<?php
// Simple search proxy: adapt query params and call products.php

require_once __DIR__ . '/../config_mysql.php';

$pdo = getPDO();
$params = $_GET;

// Return fields that the frontend expects with safe aliases
$sql = 'SELECT 
	p.id,
	p.title AS name,
	p.description,
	p.price AS price_per_unit,
	p.unit AS unit_type,
	p.quantity AS quantity_available,
	COALESCE(f.name, "") AS farm_name,
	p.image_url,
	p.farming_method AS growing_method,
	0 AS average_rating,
	0 AS review_count,
	p.category_id
  FROM products p
  LEFT JOIN farms f ON p.farmer_id = f.user_id
  WHERE 1=1';

$args = [];
// Accept either 'q' or 'search' from the frontend
$searchTerm = '';
if (!empty($params['q'])) $searchTerm = $params['q'];
if (!empty($params['search'])) $searchTerm = $params['search'];
if ($searchTerm !== '') {
	$sql .= ' AND (p.title LIKE ? OR p.description LIKE ?)';
	$args[] = '%'.$searchTerm.'%';
	$args[] = '%'.$searchTerm.'%';
}

// Farmer specific filter
if (!empty($params['farmer_id'])) {
	$sql .= ' AND p.farmer_id = ?';
	$args[] = (int)$params['farmer_id'];
}

// Categories: accept both `categories` and `categories[]` keys; values may be comma-separated
$categoryKeys = ['categories', 'categories[]'];
foreach ($categoryKeys as $ck) {
	if (!empty($params[$ck])) {
		$vals = $params[$ck];
		if (is_string($vals) && strpos($vals, ',') !== false) {
			$vals = array_map('trim', explode(',', $vals));
		} elseif (!is_array($vals)) {
			$vals = [$vals];
		}
		// map to placeholders and filter by category slug via categories table if available
		$placeholders = implode(',', array_fill(0, count($vals), '?'));
		$sql .= " AND p.category_id IN (SELECT id FROM categories WHERE slug IN ($placeholders))";
		foreach ($vals as $v) $args[] = $v;
		break;
	}
}

// Price range
if (isset($params['min_price']) && $params['min_price'] !== '') {
	$sql .= ' AND p.price >= ?';
	$args[] = (float)$params['min_price'];
}
if (isset($params['max_price']) && $params['max_price'] !== '') {
	$sql .= ' AND p.price <= ?';
	$args[] = (float)$params['max_price'];
}

// Farming methods (accept farming_methods or farming_methods[])
foreach (['farming_methods', 'farming_methods[]'] as $fmKey) {
	if (!empty($params[$fmKey])) {
		$fm = $params[$fmKey];
		if (is_string($fm) && strpos($fm, ',') !== false) $fm = array_map('trim', explode(',', $fm));
		if (!is_array($fm)) $fm = [$fm];
		$ph = implode(',', array_fill(0, count($fm), '?'));
		$sql .= " AND p.farming_method IN ($ph)";
		foreach ($fm as $m) $args[] = $m;
		break;
	}
}

$limit = 100;
$page = !empty($params['page']) ? max(1,(int)$params['page']) : 1;
$offset = ($page - 1) * $limit;
$sql .= ' LIMIT ' . (int)$limit . ' OFFSET ' . (int)$offset;
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$rows = $stmt->fetchAll();

// Optionally compute average rating and review count per product (simple approach)
foreach ($rows as &$r) {
	try {
		$pid = (int)$r['id'];
		$st = $pdo->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS cnt FROM reviews WHERE product_id = ?');
		$st->execute([$pid]);
		$agg = $st->fetch();
		$r['average_rating'] = $agg && $agg['avg_rating'] ? round((float)$agg['avg_rating'],2) : 0;
		$r['review_count'] = $agg ? (int)$agg['cnt'] : 0;
	} catch (Exception $e) {
		$r['average_rating'] = 0;
		$r['review_count'] = 0;
	}
}

echo json_encode(['success'=>true,'products'=>$rows]);

?>
