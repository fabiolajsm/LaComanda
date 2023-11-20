<?php
use \Slim\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

require_once './dao/ProductoDAO.php';

class ProductoController
{
    private $productoDAO;

    public function __construct($productoDAO)
    {
        $this->productoDAO = $productoDAO;
    }
    // ABM
    private function esProductoInvalido($nombre, $precio, $sector, $stock, $tiempoEstimado)
    {
        $sectoresPermitidos = ['A', 'B', 'C', 'D'];

        if (empty($nombre) || $precio === null || empty($sector) || $stock == null || $tiempoEstimado === null) {
            return 'Completar datos obligatorios: stock, nombre, tiempoEstimado, precio y sector.';
        }
        if (!is_numeric($stock) || $stock < 0) {
            return 'El stock debe ser un número válido mayor a 0.';
        }
        if (!is_string($nombre)) {
            return 'El nombre debe ser un texto válido.';
        }
        if (!is_numeric($precio) || $precio < 0) {
            return 'El precio debe ser un número válido mayor a 0.';
        }
        if (!is_numeric($tiempoEstimado) || $tiempoEstimado < 0) {
            return 'El tiempo estimado debe estar expresado en minutos y ser un número válido mayor a 0.';
        }
        $precio = $precio + 0.0;

        $sector = strtoupper($sector);
        if (!in_array($sector, $sectoresPermitidos)) {
            return 'Sector incorrecto. Debe ser de tipo: A (barra de tragos y vinos), B (barra de choperas de cerveza artesanal), C (cocina) y D (candy bar/postres artesanales).';
        }
        if ($this->productoDAO->obtenerProducto($nombre, $sector)) {
            return 'Ya existe el producto: ' . $nombre . ' en el sector ' . strtoupper($sector);
        }
        return false;
    }

    public function crearProducto(ServerRequest $request, ResponseInterface $response)
    {
        $data = $request->getParsedBody();
        $nombre = $data['nombre'] ?? "";
        $precio = $data['precio'] ?? null;
        $sector = $data['sector'] ?? "";
        $stock = $data['stock'] ?? null;
        $tiempoEstimado = $data['tiempoEstimado'] ?? null;
        $esInvalido = $this->esProductoInvalido($nombre, $precio, $sector, $stock, $tiempoEstimado);
        if ($esInvalido) {
            return $response->withStatus(404)->withJson(['error' => $esInvalido]);
        }

        $idProducto = $this->productoDAO->crearProducto($nombre, $precio, $sector, $stock, $tiempoEstimado);
        if ($idProducto) {
            return $response->withStatus(201)->withJson(['message' => 'Producto creado', 'id' => $idProducto]);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el producto']);
        }
    }
    public function borrarProductoPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $id = $parametros['id'] ?? null;
        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del producto que desea borrar.']);
        }
        $productoExistente = $this->productoDAO->obtenerProductoPorId($id);
        if (!$productoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Producto no encontrado']);
        }
        $borrado = $this->productoDAO->borrarProductoPorId($id);
        if ($borrado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Producto borrado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo borrar el Producto']);
        }
    }
    public function modificarProductoPorId(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getParsedBody();
        $id = $parametros['id'] ?? null;
        $nombre = $parametros['nombre'] ?? null;
        $precio = $parametros['precio'] ?? null;
        $stock = $parametros['stock'] ?? null;
        $tiempoEstimado = $parametros['tiempoEstimado'] ?? null;

        if ($id == null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el ID del producto que desea modificar.']);
        }
        $productoExistente = $this->productoDAO->obtenerProductoPorId($id);
        if (!$productoExistente) {
            return $response->withStatus(404)->withJson(['error' => 'Producto no encontrado']);
        }
        if ($nombre == null && $precio == null && $stock == null && $tiempoEstimado === null) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar algun campo que desee modificar. Los campos permitidos para modificar son: precio, nombre, stock y tiempoEstimado.']);
        }
        if ($stock !== null && !is_numeric($stock) || $stock < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El stock debe ser un número válido mayor a 0.']);
        }
        if ($nombre !== null && !is_string($nombre)) {
            return $response->withStatus(400)->withJson(['error' => 'El nombre debe ser un texto válido.']);
        }
        if ($precio !== null && !is_numeric($precio) || $precio < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El precio debe ser un número válido mayor a 0.']);
        }
        if ($tiempoEstimado !== null && !is_numeric($tiempoEstimado) || $tiempoEstimado < 0) {
            return $response->withStatus(400)->withJson(['error' => 'El tiempo estimado debe estar expresado en minutos y ser un número válido mayor a 0.']);
        }

        $nuevosDatos = [
            'ID' => $productoExistente['ID'],
            'nombre' => $nombre ?? $productoExistente['nombre'],
            'precio' => $precio ?? $productoExistente['precio'],
            'sector' => $productoExistente['sector'],
            'stock' => $stock ?? $productoExistente['stock'],
            'tiempoEstimado' => $tiempoEstimado ?? $productoExistente['tiempoEstimado'],
            'activo' => $productoExistente['activo'],
        ];
        $modificado = $this->productoDAO->modificarProducto($id, $nuevosDatos);
        if ($modificado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Producto modificado']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo modificar el producto']);
        }
    }
    // Listados
    public function listarProductos(ServerRequest $request, ResponseInterface $response)
    {
        try {
            $productos = $this->productoDAO->obtenerProductos();
            if ($productos) {
                return $response->withStatus(200)->withJson($productos);
            } else {
                return $response->withStatus(404)->withJson(['error' => 'No se encontraron productos']);
            }
        } catch (PDOException $e) {
            return $response->withStatus(500)->withJson(['error' => 'Error en la base de datos']);
        }
    }
    public function cargarProductosDesdeCSV(ServerRequest $request, ResponseInterface $response)
    {
        $uploadedFiles = $request->getUploadedFiles();
        $uploadedFile = $uploadedFiles['archivo'] ?? null;

        if ($uploadedFile === null || $uploadedFile->getError() !== UPLOAD_ERR_OK) {
            return $response->withStatus(400)->withJson(['error' => 'Debe cargar el archivo CSV']);
        }

        $csvData = $this->parseCSVFile($uploadedFile->getStream()->getContents());

        if (empty($csvData)) {
            return $response->withStatus(400)->withJson(['error' => 'El archivo CSV está vacío o no tiene un formato válido']);
        }
        $productoData = [];
        foreach ($csvData as $row) {
            for ($i = 0; $i < count($row); $i += 2) {
                $key = $row[$i] ?? null;
                $value = $row[$i + 1] ?? null;

                if ($key !== null && $value !== null) {
                    $productoData[trim($key)] = trim($value);
                }
            }
        }
        $esInvalido = $this->esProductoInvalido($productoData['nombre'], $productoData['precio'], $productoData['sector'], $productoData['stock'], $productoData['tiempoEstimado']);
        if ($esInvalido) {
            return $response->withStatus(404)->withJson(['error' => $esInvalido]);
        }
        $productoCreado = $this->productoDAO->crearProducto($productoData['nombre'], $productoData['precio'], $productoData['sector'], $productoData['stock'], $productoData['tiempoEstimado']);
        if ($productoCreado) {
            return $response->withStatus(201)->withJson(['mensaje' => 'Productos agregados desde CSV correctamente']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo crear el producto']);
        }
    }
    public function descargarProductosComoCSV(ServerRequest $request, ResponseInterface $response)
    {
        // Obtén los productos desde tu DAO
        $productos = $this->productoDAO->obtenerProductos();

        // Crea el contenido del CSV
        $csvContent = "ID,nombre,precio,sector,stock,tiempoEstimado,activo\n";
        foreach ($productos as $producto) {
            $csvContent .= implode(',', $producto) . "\n";
        }
        // Configura la respuesta para descargar el archivo CSV
        $response = $response->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', 'attachment; filename=productos.csv')
            ->withHeader('Pragma', 'no-cache')
            ->withHeader('Expires', '0')
            ->withStatus(200);
        $response->getBody()->write($csvContent);
        return $response;
    }

    // Función para parsear el contenido de un archivo CSV
    private function parseCSVFile($csvContent)
    {
        $parsedData = [];
        $lines = explode("\n", $csvContent);
        foreach ($lines as $line) {
            $parsedData[] = str_getcsv($line);
        }
        return $parsedData;
    }
    public function listarProductosSegunEmpleado(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getQueryParams();
        $tiposPermitidos = ['bartender', 'cervecero', 'cocinero', 'mozo'];
        $tipoEmpleado = $parametros['tipoEmpleado'] ?? null;

        if ($tipoEmpleado == null || !is_string($tipoEmpleado)) {
            return $response->withStatus(404)->withJson(['error' => 'Debe ingresar el tipo de empleado del que desea ver los pedidos pendientes.']);
        }
        $tipoEmpleado = strtolower($tipoEmpleado);
        if (!in_array($tipoEmpleado, $tiposPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de empleado incorrecto. Debe ser de tipo: bartender, cervecero, cocinero o mozo.']);
        }
        $sector = "";
        switch ($tipoEmpleado) {
            case 'bartender':
                $sector = "A";
                break;
            case 'cervecero':
                $sector = "B";
                break;
            case 'cocinero':
                $sector = "C";
                break;
            case 'mozo':
                $sector = "D";
                break;
            default:
                break;
        }

        $listado = $this->productoDAO->listarProductosPorSector($sector);
        if (!$listado) {
            return $response->withStatus(404)->withJson(['error' => 'Productos no encontrados']);
        }
        $idsProductos = array_column($listado, 'ID');
        $pedidos = $this->productoDAO->listarPedidosPorProductos($idsProductos);
        if (!$pedidos) {
            return $response->withStatus(404)->withJson(['error' => 'Pedidos no encontrados']);
        }
        return $response->withStatus(200)->withJson($pedidos);
    }
    public function modificarProductoSegunEmpleado(ServerRequest $request, ResponseInterface $response)
    {
        $parametros = $request->getParsedBody();

        $tipoEmpleado = $parametros['tipoEmpleado'] ?? null;
        $idPedido = $parametros['idPedido'] ?? null;
        $estado = $parametros['estado'] ?? null;

        if ($tipoEmpleado == null || $idPedido == null || $estado == null) {
            return $response->withStatus(400)->withJson(['error' => 'Debe proporcionar el tipo de empleado, el ID del pedido y el estado.']);
        }

        $tiposPermitidos = ['bartender', 'cervecero', 'cocinero'];
        $tipoEmpleado = strtolower($tipoEmpleado);

        if (!in_array($tipoEmpleado, $tiposPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Tipo de empleado incorrecto. Debe ser de tipo: bartender, cervecero, cocinero.']);
        }
        $estadosPermitidos = ['PENDIENTE', 'PROCESO', 'FINALIZADO', "LISTO PARA SERVIR"];
        $estado = strtoupper($estado);
        if (!in_array($estado, $estadosPermitidos)) {
            return $response->withStatus(400)->withJson(['error' => 'Estado incorrecto. Debe ser de tipo: PENDIENTE, PROCESO ó FINALIZADO']);
        }
        $sector = '';
        switch ($tipoEmpleado) {
            case 'bartender':
                $sector = 'A';
                break;
            case 'cervecero':
                $sector = 'B';
                break;
            case 'cocinero':
                $sector = 'C';
                break;
            case 'mozo':
                $sector = 'D';
                break;
            default:
                break;
        }
        $pedidoPerteneceAlSector = $this->productoDAO->verificarPedidoPerteneceAlSector($idPedido, $sector);
        if (!$pedidoPerteneceAlSector) {
            return $response->withStatus(400)->withJson(['error' => 'El pedido no pertenece al sector del tipo de empleado proporcionado.']);
        }
        $listado = $this->productoDAO->listarProductosPorSector($sector);
        if (!$listado) {
            return $response->withStatus(404)->withJson(['error' => 'Productos no encontrados']);
        }
        $idsProductos = array_column($listado, 'ID');
        $modificado = $this->productoDAO->modificarEstadoPedido($idPedido, $estado, $idsProductos);

        if ($modificado) {
            return $response->withStatus(200)->withJson(['mensaje' => 'Estado del pedido modificado correctamente']);
        } else {
            return $response->withStatus(500)->withJson(['error' => 'No se pudo modificar el estado del pedido']);
        }
    }

}