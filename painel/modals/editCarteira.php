    
    <?php
    ini_set('display_errors', 1);

ini_set('display_startup_errors', 1);

error_reporting(E_ALL);


    require_once(dirname(__DIR__, 2) . '/autoload.php');
session_start();

    $id=intval($_GET['id'] ?? 0);
    
    $carteira = new Carteira();
    if($id)
    $carteira->read($id);
    
    ?>
    
    
     <div class="modal fade" id="modalEdit" tabindex="-1" aria-labelledby="novaCarteiraLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                   
                    <div class="modal-header bg-dark text-light">
                        <h5 class="modal-title" id="novaCarteiraLabel">
                            <span class="material-symbols-outlined align-middle">add_circle</span>
                            Carteira
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    
                    <form id="formNovaCarteira" method="POST">
                        
                        <input type="hidden" id="carteiraId" value="<?php echo $id; ?>">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="nomeCarteira" class="form-label">Nome da Carteira</label>
                                <input type="text" value="<?php echo $carteira->nome; ?>" class="form-control" id="nomeCarteira" name="nome" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="carteiraUrl" class="form-label">Url</label>
                                <input type="text" class="form-control" id="carteiraUrl" name="url" value="<?php echo $carteira->url; ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="carteiraLogin" class="form-label">Login</label>
                                <input type="text" class="form-control" id="carteiraLogin" name="login" value="<?php echo $carteira->login; ?>">
                            </div>
                            
                           <div class="mb-3">
                                <label for="carteiraRelatorio" class="form-label">Use Relatório</label>
                                <input type="checkbox" class="form-control" id="carteiraRelatorio" name="useRelatorio" >
                            </div>
                            
                            
                            
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" onclick="Close()" data-bs-dismiss="modal" >Cancelar</button>
                            <button type="submit" class="btn btn-primary" onclick="Salvar()" id="btn-salvar">Salvar</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <script>
            $("#modalEdit").load("conteudo-modal.html", function () {
    
                // Botão dentro do conteúdo carregado
                $("#btn-salvar").on("click", function (e) {
                    e.preventDefault();
                    Salvar();
                });
            
                // Evento do Bootstrap continua igual
                $("#modalEdit").on("hidden.bs.modal", function (e) {
                    Close();
                });
            });

            
                function Salvar()
                {
                    
    const id = $('#carteiraId').val() || 0;
    const nome = $('#nomeCarteira').val();
   // const tipo = $('#tipoCarteira').val();
   // const saldo = parseFloat($('#saldoInicial').val()).toFixed(2);

    if(!nome ) return;

    $.ajax({
        url: '/app/api/carteira/save.php',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ id, nome }),
        success: function(resp)
        {
            
            if(resp.sucess){
                alert(resp.msg);
            
            }else{
                alert(resp.msg);
            }
        }
    });

                }
                
                function Close()
                {
                    $('html').remove('#modalEdit');
                }
                
            </script>
        </div>