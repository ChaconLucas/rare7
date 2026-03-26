<!-- ===== FOOTER ===== -->
<footer class="premium-footer" id="footer">
    <div class="container-shell footer-grid">
        <div>
            <h4>Marca</h4>
            <p><?php echo htmlspecialchars($footerData['marca_descricao'] ?? 'Rare7, futebol com estetica premium.'); ?></p>
            <div class="social-row">
                <?php if (!empty($footerData['instagram']) ?? false): ?>
                <a href="<?php echo htmlspecialchars($footerData['instagram']); ?>" target="_blank" rel="noopener" aria-label="Instagram">IG</a>
                <?php endif; ?>
                
                <?php if (!empty($footerData['tiktok']) ?? false): ?>
                <a href="<?php echo htmlspecialchars($footerData['tiktok']); ?>" target="_blank" rel="noopener" aria-label="TikTok">TK</a>
                <?php endif; ?>
                
                <?php if (!empty($footerData['whatsapp']) ?? false): ?>
                <a href="https://wa.me/<?php echo htmlspecialchars($footerData['whatsapp']); ?>" target="_blank" rel="noopener" aria-label="WhatsApp">WA</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div>
            <h4>Loja</h4>
            <ul>
                <?php 
                    $produtosLinks = $footerLinks['produtos'] ?? [];
                    if (!empty($produtosLinks)):
                        foreach ($produtosLinks as $link): 
                ?>
                <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['titulo']); ?></a></li>
                <?php 
                        endforeach;
                    endif;
                ?>
            </ul>
        </div>
        
        <div>
            <h4>Atendimento</h4>
            <ul>
                <?php 
                    $atendimentoLinks = $footerLinks['atendimento'] ?? [];
                    if (!empty($atendimentoLinks)):
                        foreach ($atendimentoLinks as $link): 
                ?>
                <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['titulo']); ?></a></li>
                <?php 
                        endforeach;
                    endif;
                ?>
                <li><a href="<?php echo (strpos($_SERVER['PHP_SELF'], '/pages/') !== false) ? './' : 'pages/'; ?>login.php">Minha conta</a></li>
            </ul>
        </div>
        
        <div>
            <h4>Newsletter</h4>
            <form class="newsletter-form" id="newsletterForm">
                <input type="email" placeholder="Seu melhor email" required>
                <button type="submit">Assinar</button>
            </form>
            <small>Frete gratis acima de R$ 0,00</small>
        </div>
    </div>
    
    <div class="footer-bottom container-shell">
        <span><?php echo htmlspecialchars($footerData['copyright_texto'] ?? '© 2026 Rare7. Todos os direitos reservados.'); ?></span>
    </div>
</footer>

<?php require_once __DIR__ . '/chat.php'; ?>
