<?php

require __DIR__ . '/setup.php';

// Obtener un resultado
$item = $db->table('tabla')->where('id'=>1)->select_row();

// Obtener varios resultados
$items = $db->table('tabla')->where('id_category'=>1)->order_by('campo', 'ASC')->limit(5)->select();

// Obtener número de resultados
$total = $db->table('tabla')->where('id_category'=>1)->count();

// Eliminar
$db->table('tabla')->where('id'=>1)->delete();

// Actualizar
$db->table('tabla')->set('campo', 'nuevo_valor')->where('id'=>1)->update();

// Crear
$db->table('tabla')->set('campo', 'valor')->insert();
$id_elemento = $db->insert_id();

// Última query ejecutada
echo $db->query;

// Todas las queries ejecutadas
echo '<pre>';
print_r( $db->log );
echo '</pre>';