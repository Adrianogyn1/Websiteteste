<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



require_once(dirname(__DIR__, 1) . '/autoload.php');

session_start();

header('Content-Type: application/json; charset=utf-8');

try {
    $userId = $_SESSION['id'] ?? 0;
    if (!$userId) throw new Exception("Usuário não logado");

    $carteiraId = intval($_GET['carteiraId'] ?? 0);

    $db = (new Database())->getPdo();

    // Buscar carteiras do usuário
    $sql = "SELECT * FROM Carteira WHERE PayerId = :uid";
    if ($carteiraId > 0) {
        $sql .= " AND id = :cid";
    }
    $stmt = $db->prepare($sql);

    $params = [':uid' => $userId];
    if ($carteiraId > 0) $params[':cid'] = $carteiraId;

    $stmt->execute($params);
    $carteirasData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $carteiras = [];
    foreach($carteirasData as $c) {
        $carteira = new Carteira();
        $carteira->id = $c['id'];
        $carteira->nome = $c['nome'];
        $carteira->PayerId = $c['PayerId'];
        //$carteira->db = new Database(); // injetando PDO ou Database para métodos

        // Buscar valores
       // $carteira->saldo = $carteira->GetSaldo();
      //  $carteira->depositos = $carteira->GetDepositos(new DateTime('1970-01-01'), new DateTime());
      //  $carteira->retiradas = $carteira->GetRetiradas(new DateTime('1970-01-01'), new DateTime());
     //   $carteira->lucro = $carteira->GetLucro(new DateTime('1970-01-01'), new DateTime());

        // Dados para gráfico (exemplo: últimos 7 dias)
        $labels = [];
        $data = [];
        for($i=6; $i>=0; $i--){
            $dia = new DateTime("-$i days");
            $labels[] = $dia->format('D');
            $data[] = $carteira->GetLucro(
                (clone $dia)->setTime(0,0,0),
                (clone $dia)->setTime(23,59,59)
            );
        }

        $carteira->chart_labels = $labels;
        $carteira->chart_data = $data;

        $carteiras[] = $carteira;
    }

    // Agregar todos se for "Todas"
    if ($carteiraId === 0) {
        $result = [
            'saldo' =>  0,//array_sum(array_map(fn($c) => $c->GetSaldo(), $carteiras)),
            'deposito' => 0,//array_sum(array_map(fn($c) => $c->GetDepositos(new DateTime('1970-01-01'), new DateTime()), $carteiras)),
            'retirada' => 0,//array_sum(array_map(fn($c) => $c->GetRetiradas(new DateTime('1970-01-01'), new DateTime()), $carteiras)),
            'lucro' => 0,//array_sum(array_map(fn($c) => $c->GetBalance(new DateTime('1970-01-01'), new DateTime()), $carteiras)),
            'dias' => 7,
            'avg_dia' => array_sum(array_map(fn($c) => $c->lucro, $carteiras))/7,
            'chart_labels' => $carteiras[0]->chart_labels ?? [],
            'chart_data' => array_map(fn($c) => $c->chart_data, $carteiras)
        ];
    } else {
        $c = $carteiras[0];
        $result = [
            'saldo' => $c->GetSaldo(),
            'deposito' => $c->GetDepositos(new DateTime('1970-01-01'), new DateTime()),
            'retirada' => 0,//$c->GetRetiradas(new DateTime('1970-01-01'), new DateTime()),
            'lucro' => 0,//$c->GetBalance(new DateTime('1970-01-01'), new DateTime()),
            'dias' => 7,
            'avg_dia' => $c->lucro/7,
            'chart_labels' => $c->chart_labels,
            'chart_data' => $c->chart_data
        ];
    }

    echo json_encode(['success' => true, 'msg' => 'Dashboard carregado', 'data' => $result], JSON_UNESCAPED_UNICODE);
    exit;

} catch(Exception $e) {
    echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
    exit;
}
