<?php
/**
 * CMS DATA PROVIDER - Integração Site Público
 * Fornece dados do CMS (banners, textos, produtos em destaque)
 * para o site público usando conexão mysqli existente
 * 
 * USO: 
 * require_once 'cms_data_provider.php';
 * $cms = new CMSProvider($conexao);
 */

/**
 * =====================================================
 * CLASSE CMS PROVIDER - Dependency Injection
 * =====================================================
 * Recebe conexão mysqli já existente via construtor
 */
class CMSProvider {
    
    /**
     * @var mysqli Conexão mysqli compartilhada
     */
    private $conn;
    
    /**
     * Construtor - Recebe conexão existente
     * 
     * @param mysqli $conn Conexão mysqli já configurada
     */
    public function __construct($conn) {
        if (!$conn instanceof mysqli) {
            throw new InvalidArgumentException('CMSProvider requer uma conexão mysqli válida');
        }
        $this->conn = $conn;
    }
    
    /**
     * Buscar configurações da home (textos)
     * 
     * @return array Configurações ou fallback se não existir
     */
    public function getHomeSettings() {
        try {
            $resultColumns = mysqli_query($this->conn, "SHOW COLUMNS FROM home_settings");
            if (!$resultColumns) {
                error_log("Erro ao listar colunas de home_settings: " . mysqli_error($this->conn));
                return $this->getDefaultHomeSettings();
            }

            $existingColumns = [];
            while ($column = mysqli_fetch_assoc($resultColumns)) {
                $existingColumns[$column['Field']] = true;
            }
            mysqli_free_result($resultColumns);

            $requiredColumns = [
                'hero_title',
                'hero_kicker',
                'hero_logo_path',
                'hero_subtitle',
                'hero_description',
                'hero_button_text',
                'hero_button_link',
                'launch_title',
                'launch_subtitle',
                'benefits_title',
                'benefits_subtitle',
                'launch_button_text',
                'launch_button_link',
                'products_title',
                'products_subtitle',
                'products_button_text',
                'products_button_link',
                'banner_interval'
            ];

            $selectParts = [];
            foreach ($requiredColumns as $col) {
                if (isset($existingColumns[$col])) {
                    $selectParts[] = $col;
                } else {
                    $selectParts[] = "NULL AS {$col}";
                }
            }

            $query = "
                SELECT " . implode(', ', $selectParts) . "
                FROM home_settings
                WHERE id = 1
                LIMIT 1
            ";

            $result = mysqli_query($this->conn, $query);

            if (!$result) {
                error_log("Erro ao buscar home_settings: " . mysqli_error($this->conn));
                return $this->getDefaultHomeSettings();
            }
        } catch (Throwable $e) {
            error_log("Erro em getHomeSettings: " . $e->getMessage());
            return $this->getDefaultHomeSettings();
        }
        
        $settings = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        
        // Se não houver dados, retornar fallback
        if (!$settings) {
            return $this->getDefaultHomeSettings();
        }
        
        return $settings;
    }
    
    /**
     * Buscar banners ativos (ordenados por position)
     * 
     * @return array Lista de banners ativos
     */
    public function getActiveBanners() {
        $query = "
            SELECT
                   id,
                   title,
                   subtitle,
                   description,
                   image_path,
                   button_text,
                   button_link,
                   position
            FROM home_banners 
            WHERE is_active = 1 
            ORDER BY position ASC
        ";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            error_log("Erro ao buscar banners: " . mysqli_error($this->conn));
            return [];
        }
        
        $banners = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        
        return $banners;
    }
    
    /**
     * Buscar produtos em destaque (com prepared statement)
     * 
     * @param int $limit Limite de produtos (padrão: 6)
     * @return array Lista de produtos em destaque
     */
    public function getFeaturedProducts($limit = 6) {
        $query = "
            SELECT 
                p.id,
                p.nome,
                p.descricao,
                p.preco,
                p.preco_promocional,
                p.tags,
                p.imagem_principal,
                p.slug,
                p.created_at,
                c.nome AS categoria,
                c.menu_group AS category_menu_group,
                fp.position,
                'yes' AS is_lancamento,
                (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) AS tem_variacoes
            FROM home_featured_products fp
            INNER JOIN produtos p ON fp.product_id = p.id
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE fp.section_key = 'launches'
              AND p.status = 'ativo'
              AND p.estoque > 0
            ORDER BY fp.position ASC
            LIMIT ?
        ";
        
        $stmt = mysqli_prepare($this->conn, $query);
        
        if (!$stmt) {
            error_log("Erro ao preparar query de produtos: " . mysqli_error($this->conn));
            return [];
        }
        
        // Bind do parâmetro limit (tipo i = integer)
        mysqli_stmt_bind_param($stmt, 'i', $limit);
        
        // Executar
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Erro ao executar query de produtos: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
        
        // Obter resultado
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            error_log("Erro ao obter resultado de produtos: " . mysqli_error($this->conn));
            mysqli_stmt_close($stmt);
            return [];
        }
        
        $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        
        return $products;
    }
    
    /**
     * Buscar todos os produtos ativos (para catálogo completo)
     * 
     * @param int $limit Limite de produtos (padrão: 12)
     * @return array Lista de produtos ativos
     */
    public function getAllProducts($limit = 12) {
        $query = "
            SELECT 
                p.id,
                p.nome,
                p.descricao,
                p.preco,
                p.preco_promocional,
                p.tags,
                p.imagem_principal,
                p.slug,
                p.estoque,
                p.created_at,
                c.nome AS categoria,
                c.menu_group AS category_menu_group,
                CASE WHEN fp.product_id IS NOT NULL THEN 'yes' ELSE NULL END AS is_lancamento,
                (SELECT COUNT(*) FROM produto_variacoes pv WHERE pv.produto_id = p.id AND pv.ativo = 1) AS tem_variacoes
            FROM produtos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            LEFT JOIN home_featured_products fp ON p.id = fp.product_id AND fp.section_key = 'launches'
            WHERE p.status = 'ativo'
            ORDER BY p.created_at DESC, p.id DESC
            LIMIT ?
        ";
        
        $stmt = mysqli_prepare($this->conn, $query);
        
        if (!$stmt) {
            error_log("Erro ao preparar query de todos produtos: " . mysqli_error($this->conn));
            return [];
        }
        
        // Bind do parâmetro limit (tipo i = integer)
        mysqli_stmt_bind_param($stmt, 'i', $limit);
        
        // Executar
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Erro ao executar query de todos produtos: " . mysqli_stmt_error($stmt));
            mysqli_stmt_close($stmt);
            return [];
        }
        
        // Obter resultado
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            error_log("Erro ao obter resultado de todos produtos: " . mysqli_error($this->conn));
            mysqli_stmt_close($stmt);
            return [];
        }
        
        $products = mysqli_fetch_all($result, MYSQLI_ASSOC);
        
        mysqli_free_result($result);
        mysqli_stmt_close($stmt);
        
        return $products;
    }
    
    /**
     * Buscar todos os dados com fallback
     * 
     * @return array Array com banners, settings e featured_products
     */
    public function getAllData() {
        $banners = $this->getActiveBanners();
        $settings = $this->getHomeSettings();
        $featured = $this->getFeaturedProducts(8);
        $testimonials = $this->getTestimonials(3);
        
        // Fallback para banners vazios
        if (empty($banners)) {
            $banners = [[
                'title' => 'Bem-vindo à RARE7',
                'subtitle' => 'Camisas de Times e Seleções',
                'description' => 'Descubra camisas de futebol com curadoria premium',
                'image_path' => '',
                'button_text' => 'Ver Produtos',
                'button_link' => '#catalogo'
            ]];
        }
        
        return [
            'banners' => $banners,
            'settings' => $settings,
            'featured_products' => $featured,
            'testimonials' => $testimonials
        ];
    }
    
    /**
     * Configurações padrão da home (fallback)
     * 
     * @return array
     */
    private function getDefaultHomeSettings() {
        return [
            'hero_title' => 'Bem-vindo à RARE7',
            'hero_kicker' => 'RARE EXPERIENCE',
            'hero_logo_path' => 'assets/images/logo.png',
            'hero_subtitle' => 'Camisas de Times e Seleções',
            'hero_description' => 'Vista a paixão pelo futebol com estilo e autenticidade.',
            'hero_button_text' => 'Ver Produtos',
            'hero_button_link' => '#catalogo',
            'launch_title' => 'Lançamentos',
            'launch_subtitle' => 'Conheça as novidades exclusivas que acabaram de chegar na RARE7',
            'benefits_title' => 'Beneficios Rare',
            'benefits_subtitle' => 'Acabamento premium e experiencia de compra refinada.',
            'launch_button_text' => 'Ver Todos os Lançamentos',
            'launch_button_link' => '#catalogo',
            'products_title' => 'Todos os Produtos',
            'products_subtitle' => '',
            'products_button_text' => 'Ver Depoimentos',
            'products_button_link' => '#depoimentos',
            'banner_interval' => 6
        ];
    }
    
    /**
     * Buscar benefícios da home (cards de vantagens)
     * 
     * @return array Lista de benefícios ativos
     */
    public function getHomeBenefits() {
        $query = "
            SELECT icone, titulo, subtitulo AS descricao, cor
            FROM cms_home_beneficios 
            WHERE ativo = 1 
            ORDER BY ordem ASC
            LIMIT 4
        ";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            error_log("Erro ao buscar benefícios: " . mysqli_error($this->conn));
            return $this->getDefaultBenefits();
        }
        
        $benefits = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        
        // Se não houver dados, retornar fallback
        if (empty($benefits)) {
            return $this->getDefaultBenefits();
        }
        
        return $benefits;
    }
    
    /**
     * Buscar dados do footer
     * 
     * @return array Dados do footer ou fallback
     */
    public function getFooterData() {
        $query = "
            SELECT marca_titulo, marca_subtitulo, marca_descricao,
                   telefone, whatsapp, email,
                   instagram, tiktok, facebook,
                   copyright_texto
            FROM cms_footer 
            WHERE id = 1
            LIMIT 1
        ";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            error_log("Erro ao buscar footer: " . mysqli_error($this->conn));
            return $this->getDefaultFooter();
        }
        
        $footer = mysqli_fetch_assoc($result);
        mysqli_free_result($result);
        
        // Se não houver dados, retornar fallback
        if (!$footer) {
            return $this->getDefaultFooter();
        }
        
        return $footer;
    }
    
    /**
     * Buscar links do footer agrupados por coluna
     * 
     * @return array ['produtos' => [...], 'atendimento' => [...]]
     */
    public function getFooterLinks() {
        $query = "
            SELECT texto AS titulo, link AS url, coluna 
            FROM cms_footer_links 
            WHERE ativo = 1 
            ORDER BY coluna, ordem ASC
        ";
        
        $result = mysqli_query($this->conn, $query);
        
        if (!$result) {
            error_log("Erro ao buscar footer links: " . mysqli_error($this->conn));
            return $this->getDefaultFooterLinks();
        }
        
        $all_links = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_free_result($result);
        
        // Agrupar por coluna
        $grouped = [
            'produtos' => [],
            'atendimento' => []
        ];
        
        foreach ($all_links as $link) {
            $col = $link['coluna'];
            if (isset($grouped[$col])) {
                $grouped[$col][] = [
                    'titulo' => $link['titulo'],
                    'url' => $link['url']
                ];
            }
        }
        
        // Se estiverem vazios, usar fallback
        if (empty($grouped['produtos']) && empty($grouped['atendimento'])) {
            return $this->getDefaultFooterLinks();
        }
        
        return $grouped;
    }
    
    /**
     * Benefícios padrão (fallback)
     * 
     * @return array
     */
    private function getDefaultBenefits() {
        return [
            ['icone' => 'local_shipping', 'titulo' => 'Entrega Grátis', 'descricao' => 'Acima de R$ 200'],
            ['icone' => 'verified', 'titulo' => 'Qualidade Premium', 'descricao' => 'Produtos originais'],
            ['icone' => 'sync', 'titulo' => 'Troca Fácil', 'descricao' => 'Em até 30 dias'],
            ['icone' => 'support_agent', 'titulo' => 'Suporte 24h', 'descricao' => 'Sempre disponível']
        ];
    }
    
    /**
     * Footer padrão (fallback)
     * 
     * @return array
     */
    private function getDefaultFooter() {
        return [
            'marca_titulo' => 'RARE7',
            'marca_subtitulo' => 'Camisas de Futebol',
            'marca_descricao' => 'Camisas de clubes e seleções com qualidade premium para quem vive futebol.',
            'telefone' => '(11) 98765-4321',
            'whatsapp' => '5511987654321',
            'email' => 'contato@rare7.com.br',
            'instagram' => 'https://instagram.com/rare7oficial',
            'tiktok' => 'https://tiktok.com/@rare7oficial',
            'facebook' => 'https://facebook.com/rare7oficial',
            'copyright_texto' => '2024 RARE7. Todos os direitos reservados.'
        ];
    }
    
    /**
     * Links do footer padrão (fallback)
     * 
     * @return array
     */
    private function getDefaultFooterLinks() {
        return [
            'produtos' => [
                ['titulo' => 'Camisas de Clubes', 'url' => '#clubes'],
                ['titulo' => 'Camisas de Seleções', 'url' => '#selecoes'],
                ['titulo' => 'Coleção Retro', 'url' => '#retro'],
                ['titulo' => 'Lançamentos', 'url' => '#lancamentos']
            ],
            'atendimento' => [
                ['titulo' => 'Sobre Nós', 'url' => '#sobre'],
                ['titulo' => 'Política de Entrega', 'url' => '#entrega'],
                ['titulo' => 'Trocas e Devoluções', 'url' => '#trocas'],
                ['titulo' => 'Perguntas Frequentes', 'url' => '#faq'],
                ['titulo' => 'Trabalhe Conosco', 'url' => '#trabalhe']
            ]
        ];
    }
    
    /**
     * Buscar promoções ativas (para banner promocional)
     * 
     * @return array Lista de promoções ativas dentro do período
     */
    public function getActivePromotions() {
        $hoje = date('Y-m-d');
        
        $query = "
            SELECT p.*, c.codigo as cupom_codigo
            FROM cms_home_promotions p
            LEFT JOIN cupons c ON p.cupom_id = c.id
            WHERE p.ativo = 1
            AND p.data_inicio <= ?
            AND p.data_fim >= ?
            ORDER BY p.ordem ASC, p.id DESC
        ";
        
        $stmt = mysqli_prepare($this->conn, $query);
        
        if (!$stmt) {
            error_log("Erro ao buscar promoções: " . mysqli_error($this->conn));
            return [];
        }
        
        mysqli_stmt_bind_param($stmt, 'ss', $hoje, $hoje);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result) {
            mysqli_stmt_close($stmt);
            return [];
        }
        
        $promotions = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        return $promotions;
    }
    
    /**
     * Buscar métricas ativas (ex: "98% - Clientes satisfeitas")
     * 
     * @return array Lista de métricas ativas ordenadas por ordem
     */
    public function getActiveMetrics() {
        try {
            $query = "
                SELECT valor, label, tipo, ordem
                FROM cms_home_metrics
                WHERE ativo = 1
                ORDER BY ordem ASC, id ASC
            ";
            
            $result = mysqli_query($this->conn, $query);
            
            if (!$result) {
                error_log("Erro ao buscar métricas: " . mysqli_error($this->conn));
                return [];
            }
            
            $metrics = mysqli_fetch_all($result, MYSQLI_ASSOC);
            
            return $metrics;
        } catch (Exception $e) {
            // Tabela ainda não existe - retornar array vazio para não quebrar o site
            error_log("Tabela cms_home_metrics não encontrada: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Buscar depoimentos ativos
     * 
     * @param int $limit Número máximo de depoimentos (padrão: 3)
     * @return array Lista de depoimentos ativos
     */
    public function getTestimonials($limit = 3) {
        try {
            $limit = (int)$limit;
            $hasProductImageColumn = false;
            $columnCheck = mysqli_query($this->conn, "SHOW COLUMNS FROM cms_testimonials LIKE 'product_image_path'");
            if ($columnCheck && mysqli_num_rows($columnCheck) > 0) {
                $hasProductImageColumn = true;
            }

            $productImageSelect = $hasProductImageColumn
                ? "product_image_path"
                : "NULL AS product_image_path";

            $query = "
                SELECT nome, cargo_empresa, texto, rating, avatar_path, {$productImageSelect}
                FROM cms_testimonials
                WHERE ativo = 1
                ORDER BY ordem ASC, id DESC
                LIMIT {$limit}
            ";
            
            $result = mysqli_query($this->conn, $query);
            
            if (!$result) {
                error_log("Erro ao buscar depoimentos: " . mysqli_error($this->conn));
                return [];
            }
            
            $testimonials = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
            
            return $testimonials;
        } catch (Exception $e) {
            // Tabela ainda não existe - retornar array vazio para não quebrar o site
            error_log("Tabela cms_testimonials não encontrada: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * =====================================================
 * HELPER FUNCTIONS - Funções Auxiliares
 * =====================================================
 */

/**
 * Helper: URL da imagem do banner (caminho relativo correto)
 * 
 * @param string $image_path Caminho da imagem do banco
 * @return string Caminho relativo correto ou vazio
 */
function getBannerImageUrl($image_path) {
    // Se vazio, retornar vazio
    if (empty($image_path)) {
        return '';
    }
    
    // Se começar com http/https, retornar direto (URL externa)
    if (preg_match('#^https?://#i', $image_path)) {
        return $image_path;
    }
    
    // Sanitizar: remover barras duplicadas e path traversal
    $image_path = str_replace(['../', '.\\'], '', $image_path);
    $image_path = preg_replace('#/+#', '/', $image_path);
    
    // Se o caminho não incluir 'uploads/banners/', adicionar
    if (strpos($image_path, 'uploads/banners/') === false) {
        $image_path = 'uploads/banners/' . ltrim($image_path, '/');
    }
    
    // Caminho relativo ao index.php (que está em cliente/)
    // index.php está em: admin-teste/cliente/
    // Upload está em: admin-teste/uploads/banners/
    // Então: ../ sobe para admin-teste/
    return '../' . ltrim($image_path, '/');
}

/**
 * Helper: Preço formatado em R$
 * 
 * @param float $price Preço em decimal
 * @return string Preço formatado (ex: R$ 24,90)
 */
function formatPrice($price) {
    return 'R$ ' . number_format($price, 2, ',', '.');
}

/**
 * Helper: Verificar se produto está em promoção
 * 
 * @param array $product Array do produto
 * @return bool True se em promoção
 */
function isOnSale($product) {
    return isset($product['preco_promocional']) 
           && $product['preco_promocional'] > 0 
           && $product['preco_promocional'] < $product['preco'];
}

/**
 * Helper: Determinar qual badge/selo deve ser exibido no card
 * 
 * @param array $product Array do produto
 * @return string Classe CSS do badge ('lancamento', 'novo', ou '' para nenhum)
 */
function getProductBadge($product) {
    // Prioridade 1: Verificar se é lançamento (produto selecionado no CMS)
    if (isset($product['is_lancamento']) && $product['is_lancamento'] === 'yes') {
        return 'lancamento';
    }
    
    // Prioridade 2: Verificar se é novo (últimos 15 dias)
    if (isset($product['created_at']) && !empty($product['created_at'])) {
        $createdDate = new DateTime($product['created_at']);
        $now = new DateTime();
        $diff = $now->diff($createdDate);
        $daysDiff = $diff->days;
        
        // Se foi criado há menos de 15 dias
        if ($daysDiff <= 15) {
            return 'novo';
        }
    }
    
    // Sem badge
    return '';
}

/**
 * Obter tags de produto (chips visuais) - customizadas e automáticas
 * 
 * @param array $product Dados do produto
 * @return array Array com tags para renderização
 */
function getProductTags($product) {
    // Regra 1: se estiver marcado como lançamento no painel CMS, usa tag automática
    if (isset($product['is_lancamento']) && (string) $product['is_lancamento'] === 'yes') {
        return [[
            'type' => 'lancamento',
            'label' => 'LANCAMENTO',
        ]];
    }

    // Regra 2: fora lançamentos, usa a única tag configurada no produto (se existir)
    if (isset($product['tags']) && trim((string) $product['tags']) !== '') {
        $rawTags = explode(',', (string) $product['tags']);
        $firstTag = trim((string) ($rawTags[0] ?? ''));
        if ($firstTag !== '') {
            return [[
                'type' => 'custom',
                'label' => mb_strtoupper($firstTag),
            ]];
        }
    }

    // Regra 3: se não tem tag manual e foi criado nos últimos 15 dias, usa NOVO
    if (isset($product['created_at']) && !empty($product['created_at'])) {
        $createdDate = new DateTime($product['created_at']);
        $now = new DateTime();
        $daysDiff = $now->diff($createdDate)->days;

        if ($daysDiff <= 15) {
            return [[
                'type' => 'novo',
                'label' => 'NOVO',
            ]];
        }
    }

    return [];
}

/**
 * Helper: Calcular desconto percentual
 * 
 * @param float $regular_price Preço regular
 * @param float $sale_price Preço promocional
 * @return int Percentual de desconto
 */
function getDiscountPercentage($regular_price, $sale_price) {
    if ($sale_price >= $regular_price) return 0;
    return round((($regular_price - $sale_price) / $regular_price) * 100);
}

