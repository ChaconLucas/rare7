<aside>
    <div class="top">
        <div class="logo">
            <img src="../../../../assets/images/logo_png.png" alt="Logo">
            <a href="../index.php"><h2 class="danger">Rare7</h2></a>
        </div>
        <div class="close" id="close-btn">
            <span class="material-symbols-sharp">close</span>
        </div>
    </div>

    <div class="sidebar">
        <a href="../index.php">
            <span class="material-symbols-sharp">grid_view</span>
            <h3>Painel</h3>
        </a>
        <a href="../customers.php">
            <span class="material-symbols-sharp">group</span>
            <h3>Clientes</h3>
        </a>
        <a href="../orders.php">
            <span class="material-symbols-sharp">Orders</span>
            <h3>Pedidos</h3>
        </a>
        <a href="../analytics.php">
            <span class="material-symbols-sharp">Insights</span>
            <h3>Gráficos</h3>
        </a>
        <a href="../menssage.php">
            <span class="material-symbols-sharp">Mail</span>
            <h3>Mensagens</h3>
            <span class="message-count"><?php echo $nao_lidas ?? 0; ?></span>
        </a>
        <a href="../products.php">
            <span class="material-symbols-sharp">Inventory</span>
            <h3>Produtos</h3>
        </a>
        <a href="../cupons.php">
            <span class="material-symbols-sharp">sell</span>
            <h3>Cupons</h3>
        </a>
        <a href="../gestao-fluxo.php">
            <span class="material-symbols-sharp">account_tree</span>
            <h3>Gestão de Fluxo</h3>
        </a>

        <div class="menu-item-container">
          <a href="home.php" class="menu-item-with-submenu">
              <span class="material-symbols-sharp">web</span>
              <h3>CMS</h3>
          </a>
          
          <div class="submenu">
            <a href="home.php">
              <span class="material-symbols-sharp">home</span>
              <h3>Home (Textos)</h3>
            </a>
            <a href="banners.php">
              <span class="material-symbols-sharp">view_carousel</span>
              <h3>Banners</h3>
            </a>
            <a href="featured.php">
              <span class="material-symbols-sharp">star</span>
              <h3>Lançamentos</h3>
            </a>
            <a href="promos.php">
              <span class="material-symbols-sharp">local_offer</span>
              <h3>Promoções</h3>
            </a>
            <a href="testimonials.php">
              <span class="material-symbols-sharp">format_quote</span>
              <h3>Depoimentos</h3>
            </a>
            <a href="metrics.php">
              <span class="material-symbols-sharp">speed</span>
              <h3>Métricas</h3>
            </a>
          </div>
        </div>
        
        <div class="menu-item-container">
          <a href="../geral.php" class="menu-item-with-submenu">
              <span class="material-symbols-sharp">Settings</span>
              <h3>Configurações</h3>
          </a>
          
          <div class="submenu">
            <a href="../geral.php">
              <span class="material-symbols-sharp">tune</span>
              <h3>Geral</h3>
            </a>
            <a href="../pagamentos.php">
              <span class="material-symbols-sharp">payments</span>
              <h3>Pagamentos</h3>
            </a>
            <a href="../frete.php">
              <span class="material-symbols-sharp">local_shipping</span>
              <h3>Frete</h3>
            </a>
            <a href="../automacao.php">
              <span class="material-symbols-sharp">automation</span>
              <h3>Automação</h3>
            </a>
            <a href="../metricas.php">
              <span class="material-symbols-sharp">analytics</span>
              <h3>Métricas</h3>
            </a>
            <a href="../settings.php">
              <span class="material-symbols-sharp">group</span>
              <h3>Usuários</h3>
            </a>
          </div>
        </div>
        
        <a href="../revendedores.php">
            <span class="material-symbols-sharp">handshake</span>
            <h3>Revendedores</h3>
        </a>
        <a href="../../../../PHP/logout.php">
            <span class="material-symbols-sharp">Logout</span>
            <h3>Sair</h3>
        </a>
    </div>
</aside>

