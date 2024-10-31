<?php

$errores = [];
$datos = [];


$directorioFotos = __DIR__ . '/fotos/';


if (!file_exists($directorioFotos)) {
    mkdir($directorioFotos, 0777, true);
}


$archivoViviendas = __DIR__ . '/vivienda.xml';


if (!file_exists($archivoViviendas)) {
    $xml = new SimpleXMLElement('<viviendas></viviendas>');
    $xml->asXML($archivoViviendas);
}

$beneficios = [
    'Centro' => ['menos_100' => 0.30, 'mas_100' => 0.35],
    'Zaidín' => ['menos_100' => 0.25, 'mas_100' => 0.28],
    'Chana' => ['menos_100' => 0.22, 'mas_100' => 0.25],
    'Albaicín' => ['menos_100' => 0.20, 'mas_100' => 0.35],
    'Sacromonte' => ['menos_100' => 0.22, 'mas_100' => 0.25],
    'Realejo' => ['menos_100' => 0.25, 'mas_100' => 0.28],
];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $tipo = $_POST["tipo"] ?? null;
    $zona = $_POST["zona"] ?? null;
    $direccion = trim($_POST["direccion"]) ?? '';
    $dormitorios = (int)($_POST["dormitorios"] ?? 0);
    $precio = (float)($_POST["precio"] ?? 0);
    $tamano = (int)($_POST["tamano"] ?? 0);
    $extras = $_POST['extras'] ?? [];
    $observacion = htmlspecialchars(trim($_POST["observaciones"] ?? ''), ENT_QUOTES, 'UTF-8');

 
    if (empty($tipo)) $errores[] = "La vivienda debe de ser seleccionada.";
    if (empty($zona)) $errores[] = "La zona tiene que ser seleccionada.";
    if (empty($direccion)) $errores[] = "La dirección es un campo requerido.";
    if ($dormitorios < 1 || $dormitorios > 5) $errores[] = "El número de dormitorios debe estar entre 1 y 5.";
    if ($precio <= 0) $errores[] = "El precio debe ser un número positivo.";
    if ($tamano <= 0) $errores[] = "El tamaño en metros cuadrados debe ser un número positivo.";


    if (isset($_FILES["foto"]) && $_FILES["foto"]["error"] == 0) {
        $foto = $_FILES["foto"];
        if ($foto["size"] > 100 * 1024) $errores[] = "El archivo de la foto no debe exceder los 100 KB.";
        $nombreArchivo = basename($foto["name"]);
        $directorioDestino = $directorioFotos . $nombreArchivo;
        $tipoArchivo = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
        $tiposPermitidos = ["jpg", "jpeg", "png"];
        if (!in_array($tipoArchivo, $tiposPermitidos)) {
            $errores[] = "Solo se permiten archivos de los formatos: JPG, JPEG, PNG.";
        } else {
            if (move_uploaded_file($foto["tmp_name"], $directorioDestino)) {
                $datos["foto"] = "fotos/" . $nombreArchivo;
            } else {
                $errores[] = "Error al subir la foto.";
            }
        }
    } else {
        $errores[] = "No se ha subido ninguna foto.";
    }


    if (empty($errores)) {
        $datos = [
            "tipo" => $tipo,
            "zona" => $zona,
            "direccion" => $direccion,
            "dormitorios" => $dormitorios,
            "precio" => $precio,
            "tamano" => $tamano,
            "extras" => $extras,
            "foto" => $datos["foto"],
            "observacion" => $observacion,
        ];

        $tipoTamano = $tamano < 100 ? 'menos_100' : 'mas_100';
        if (array_key_exists($zona, $beneficios)) {
            $porcentajeBeneficio = $beneficios[$zona][$tipoTamano];
            $beneficio = $precio * $porcentajeBeneficio;
        } else {
            $beneficio = 0; // Zona no encontrada
        }
       
        $xml = simplexml_load_file($archivoViviendas);
       
        $nuevaVivienda = $xml->addChild('vivienda');
      
        $nuevaVivienda->addChild('tipoVivienda', $datos['tipo']);
        $nuevaVivienda->addChild('zonaVivienda', $datos['zona']);
        $nuevaVivienda->addChild('direccionVivienda', htmlspecialchars($datos['direccion']));
        $nuevaVivienda->addChild('dormitorios', $datos['dormitorios']);
        $nuevaVivienda->addChild('precio', $datos['precio']);
        $nuevaVivienda->addChild('tamano', $datos['tamano']);
        $nuevaVivienda->addChild('extras', implode(', ', array_map('htmlspecialchars', $datos['extras'])));
        $nuevaVivienda->addChild('foto', htmlspecialchars($datos['foto']));
        $nuevaVivienda->addChild('observacion', $datos['observacion']);

     
        $xml->asXML($archivoViviendas);
        echo "<p>Datos guardados correctamente en el archivo XML.</p>";
    } else {
    
        echo "<h2>Errores en el formulario:</h2>";
        foreach ($errores as $error) {
            echo "<p style='color: red;'>$error</p>";
        }
    }

  
    if (empty($errores)) {
        echo "<h3>Datos de la vivienda insertados correctamente:</h3>";
        echo "<table class='data-table'>
                <tr><th>Tipo de Vivienda</th><td>{$datos['tipo']}</td></tr>
                <tr><th>Zona en la que se encuentra</th><td>{$datos['zona']}</td></tr>
                <tr><th>Dirección de la vivienda</th><td>" . htmlspecialchars($datos['direccion']) . "</td></tr>
                <tr><th>Dormitorios que tiene dicha vivienda</th><td>{$datos['dormitorios']}</td></tr>
                <tr><th>Precio del inmobiliario</th><td>{$datos['precio']} €</td></tr>
                <tr><th>Beneficio Calculado</th><td>{$beneficio} €</td></tr>
                <tr><th>Tamaño</th><td>{$datos['tamano']} m²</td></tr>
                <tr><th>Extras que tiene</th><td>" . implode(', ', array_map('htmlspecialchars', $datos['extras'])) . "</td></tr>
                <tr><th>Observaciones a tener en cuenta</th><td>" . htmlspecialchars($datos['observacion']) . "</td></tr>
              </table>";

       
        if (isset($datos['foto']) && file_exists($datos['foto'])) {
            echo "<h4>Foto:</h4><img src='{$datos['foto']}' alt='Foto de la vivienda' />";
        }

       
        echo "<br><button onclick=\"window.location.href='formulario.html'\">Insertar mas viviendas</button>";
    }
}
?>
