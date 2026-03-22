<!-- ===== FOOTER ===== -->
<footer class="footer-modern">
    <div class="container-dz">
        <div class="footer-content">
            <div class="footer-top">
                <div class="footer-brand">
                    <div class="brand-logo">
                        <h3><?php echo htmlspecialchars($footerData['marca_titulo'] ?? 'D&Z'); ?></h3>
                        <div class="brand-tagline"><?php echo htmlspecialchars($footerData['marca_subtitulo'] ?? 'Beauty & Style'); ?></div>
                    </div>
                    
                    <p class="brand-description">
                        <?php echo htmlspecialchars($footerData['marca_descricao'] ?? 'Transformando a beleza das mulheres brasileiras com produtos premium e atendimento excepcional.'); ?>
                    </p>
                    
                    <div class="footer-social-main">
                        <div class="social-links-grid">
                            <?php if (!empty($footerData['instagram'])): ?>
                            <a href="<?php echo htmlspecialchars($footerData['instagram']); ?>" target="_blank" class="social-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" class="social-icon">
                                    <path fill="#E4405F" d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($footerData['tiktok'])): ?>
                            <a href="<?php echo htmlspecialchars($footerData['tiktok']); ?>" target="_blank" class="social-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" class="social-icon">
                                    <path fill="#000" d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($footerData['whatsapp'])): ?>
                            <a href="https://wa.me/<?php echo htmlspecialchars($footerData['whatsapp']); ?>" target="_blank" class="social-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" class="social-icon">
                                    <path fill="#25D366" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.465 3.488"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                            
                            <?php if (!empty($footerData['facebook'])): ?>
                            <a href="<?php echo htmlspecialchars($footerData['facebook']); ?>" target="_blank" class="social-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" class="social-icon">
                                    <path fill="#1877F2" d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="footer-links">
                    <div class="footer-column">
                        <h5>Produtos</h5>
                        <ul>
                            <?php foreach ($footerLinks['produtos'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['titulo']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h5>Atendimento</h5>
                        <ul>
                            <?php foreach ($footerLinks['atendimento'] as $link): ?>
                            <li><a href="<?php echo htmlspecialchars($link['url']); ?>"><?php echo htmlspecialchars($link['titulo']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    
                    <div class="footer-column">
                        <h5>Contato</h5>
                        <div class="contact-info">
                            <?php if (!empty($footerData['telefone'])): ?>
                            <div class="contact-item">
                                <span class="contact-icon">📞</span>
                                <span><?php echo htmlspecialchars($footerData['telefone']); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($footerData['whatsapp'])): ?>
                            <div class="contact-item">
                                <span class="contact-icon">💬</span>
                                <span>WhatsApp 24h</span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($footerData['email'])): ?>
                            <div class="contact-item">
                                <span class="contact-icon">✉️</span>
                                <span><?php echo htmlspecialchars($footerData['email']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-security">
                <div class="trust-badge">
                    <h6>Formas de pagamento</h6>
                    <div class="payment-icons">
                        <!-- Visa -->
                        <svg width="28" height="18" viewBox="0 0 780 500" class="payment-icon">
                            <path fill="#1434CB" d="M40 0h700c22 0 40 18 40 40v420c0 22-18 40-40 40H40c-22 0-40-18-40-40V40C0 18 18 0 40 0z"/>
                            <text x="390" y="350" text-anchor="middle" font-size="180" fill="#FFF" font-family="Arial, sans-serif" font-weight="bold">VISA</text>
                        </svg>
                        <!-- Mastercard -->
                        <svg width="28" height="18" viewBox="0 0 780 500" class="payment-icon">
                            <rect width="780" height="500" rx="40" fill="#000"/>
                            <circle cx="270" cy="250" r="125" fill="#EB001B"/>
                            <circle cx="510" cy="250" r="125" fill="#F79E1B"/>
                            <path d="M380 135.6v44.9c-11-17-30-28.4-51.6-28.4-35.3 0-64 28.7-64 64s28.7 64 64 64c21.6 0 40.6-11.4 51.6-28.4v44.9h32v-158H380v-.8z" fill="#FF5F00"/>
                        </svg>
                        <!-- PIX -->
                        <svg width="28" height="18" viewBox="0 0 512 512" class="payment-icon">
                            <rect width="512" height="512" rx="45" fill="#32BCAD"/>
                            <text x="256" y="280" text-anchor="middle" font-size="120" fill="#FFF" font-family="Arial, sans-serif" font-weight="bold">PIX</text>
                        </svg>
                        <!-- Boleto -->
                        <svg width="28" height="18" viewBox="0 0 100 64" class="payment-icon">
                            <rect width="100" height="64" rx="4" fill="#333" stroke="#666"/>
                            <rect x="4" y="8" width="92" height="2" fill="#FFF"/>
                            <rect x="4" y="12" width="92" height="2" fill="#FFF"/>
                            <rect x="4" y="16" width="70" height="2" fill="#FFF"/>
                            <rect x="4" y="20" width="85" height="2" fill="#FFF"/>
                            <text x="50" y="45" text-anchor="middle" font-size="10" fill="#FFF" font-family="Arial, sans-serif" font-weight="bold">BOLETO</text>
                        </svg>
                        <!-- Cartão de Crédito -->
                        <svg width="28" height="18" viewBox="0 0 100 64" class="payment-icon">
                            <rect width="100" height="64" rx="6" fill="#4A90E2" stroke="#357ABD"/>
                            <rect y="20" width="100" height="12" fill="#357ABD"/>
                            <rect x="8" y="42" width="20" height="4" fill="#FFF"/>
                            <circle cx="85" cy="50" r="4" fill="#FFF"/>
                        </svg>
                        <!-- Cartão de Débito -->
                        <svg width="28" height="18" viewBox="0 0 100 64" class="payment-icon">
                            <rect width="100" height="64" rx="6" fill="#28A745" stroke="#1E7E34"/>
                            <rect y="20" width="100" height="12" fill="#1E7E34"/>
                            <text x="8" y="55" font-size="8" fill="#FFF" font-family="Arial, sans-serif" font-weight="bold">DÉBITO</text>
                        </svg>
                    </div>
                </div>
                
                <div class="trust-badge">
                    <div class="ssl-protection">
                        <svg width="20" height="20" viewBox="0 0 24 24" class="ssl-icon">
                            <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M10,17L6,13L7.41,11.59L10,14.17L16.59,7.58L18,9L10,17Z" fill="#2ECC71"/>
                        </svg>
                        <span class="ssl-text">SSL</span>
                    </div>
                </div>
                
                <div class="trust-badge">
                    <!-- CE -->
                    <svg width="20" height="20" viewBox="0 0 100 100" class="trust-icon">
                        <rect width="100" height="100" rx="8" fill="#003399"/>
                        <text x="50" y="60" text-anchor="middle" font-size="28" fill="#FFF" font-family="Arial, sans-serif" font-weight="bold">CE</text>
                    </svg>
                </div>
                
                <div class="trust-badge">
                    <!-- ISO 9001 -->
                    <svg width="20" height="20" viewBox="0 0 100 100" class="trust-icon">
                        <circle cx="50" cy="50" r="45" fill="#FFF" stroke="#000" stroke-width="2"/>
                        <text x="50" y="35" text-anchor="middle" font-size="12" fill="#000" font-family="Arial, sans-serif" font-weight="bold">ISO</text>
                        <text x="50" y="50" text-anchor="middle" font-size="16" fill="#000" font-family="Arial, sans-serif" font-weight="bold">9001</text>
                        <text x="50" y="65" text-anchor="middle" font-size="8" fill="#000" font-family="Arial, sans-serif">Quality</text>
                    </svg>
                </div>
                
                <div class="trust-badge">
                    <!-- Google Safe Browsing -->
                    <svg width="20" height="20" viewBox="0 0 100 100" class="trust-icon">
                        <rect width="100" height="100" rx="8" fill="#1a73e8"/>
                        <path d="M50 20L30 40v30l20 10 20-10V40L50 20z" fill="#34a853"/>
                        <path d="M45 45h10v20h-10V45z" fill="#FFF"/>
                        <circle cx="50" cy="38" r="3" fill="#FFF"/>
                    </svg>
                </div>
                
                <div class="trust-badge">
                    <!-- CO2 Neutral -->
                    <svg width="20" height="20" viewBox="0 0 100 100" class="trust-icon">
                        <circle cx="50" cy="50" r="45" fill="#228B22"/>
                        <text x="50" y="40" text-anchor="middle" font-size="14" fill="#FFF" font-family="Arial, sans-serif" font-weight="bold">CO₂</text>
                        <text x="50" y="60" text-anchor="middle" font-size="10" fill="#FFF" font-family="Arial, sans-serif">NEUTRAL</text>
                    </svg>
                </div>
        </div>
    </div>
    
    <div class="footer-bottom">
        <div class="copyright">
            <?php echo htmlspecialchars($footerData['copyright_texto'] ?? '© 2024 D&Z Beauty • Todos os direitos reservados'); ?>
        </div>
    </div>
</footer>
</body>
</html>
