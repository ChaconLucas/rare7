<?php
// Mini Cart Component - Carrinho Lateral Compartilhado
// Este componente requer:
// - $basePath (string opcional): caminho relativo para a raiz
// - $freteGratisValor (float opcional): valor do frete grátis do banco de dados

$miniCartBasePath = isset($basePath) ? (string)$basePath : ((strpos($_SERVER['PHP_SELF'] ?? '', '/pages/') !== false) ? '../' : '');
$miniCartCartUrl = $miniCartBasePath . 'pages/carrinho.php';
$miniCartFallbackImage = $miniCartBasePath . 'image/logo_png.png';
$miniCartFreeShipping = isset($freteGratisValor) ? (float)$freteGratisValor : 500.00; // Fallback: 500.00

// Debug info
error_log("[MiniCart] Inicializado com freteGratisValor=" . json_encode([
    'definido' => isset($freteGratisValor),
    'valor' => $miniCartFreeShipping,
    'basePath' => $miniCartBasePath,
    'page' => $_SERVER['PHP_SELF'] ?? 'unknown'
]));
?>
<div id="miniCartOverlay" class="mini-cart-overlay"></div>
<div id="miniCartDrawer" class="mini-cart-drawer">
    <div class="mini-cart-header">
        <h2>Seu carrinho</h2>
        <button id="closeMiniCart" class="btn-close-cart" aria-label="Fechar carrinho">
            <span class="material-symbols-sharp">close</span>
        </button>
    </div>

    <div class="mini-cart-body" id="miniCartBody"></div>

    <div class="mini-cart-footer">
        <div class="free-shipping-bar" id="freeShippingBar"></div>
        <div class="mini-cart-subtotal">
            <span>Subtotal:</span>
            <strong id="miniCartSubtotal">R$ 0,00</strong>
        </div>
        <a href="<?php echo htmlspecialchars($miniCartCartUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn-view-cart">Ver carrinho completo</a>
    </div>
</div>

<style>
.free-shipping-bar {
    margin-bottom: 12px;
}

.shipping-text {
    font-size: 0.82rem;
    color: #1e293b;
    margin-bottom: 8px;
    font-weight: 600;
    text-align: center;
}

.shipping-progress {
    height: 6px;
    overflow: hidden;
    border-radius: 3px;
    background: rgba(255, 255, 255, 0.7);
}

.shipping-progress-bar {
    height: 100%;
    background: linear-gradient(90deg, #c6a75e, #f1ddb0);
    border-radius: 4px;
    transition: width 0.45s ease;
}

.shipping-unlocked {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: #059669;
    font-weight: 600;
    font-size: 0.9rem;
}
</style>

<script>
(function () {
    if (window.RareMiniCart) {
        window.RareMiniCart.sync();
        return;
    }

    const config = {
        cartUrl: <?php echo json_encode($miniCartCartUrl, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        fallbackImage: <?php echo json_encode($miniCartFallbackImage, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>,
        freeShippingThreshold: <?php echo json_encode($miniCartFreeShipping); ?>
    };

    // Debug: informações detalhadas
    console.group('[RareMiniCart] Inicialização');
    console.log('Valor do frete grátis:', config.freeShippingThreshold, 'BRL');
    console.log('Origem:', 'Banco de dados (freight_settings.free_shipping_threshold)');
    console.log('Fallback:', 500.00);
    console.table({
        'Threshold': config.freeShippingThreshold,
        'URL Carrinho': config.cartUrl,
        'Imagem Fallback': config.fallbackImage
    });
    console.groupEnd();

    function getCart() {
        try {
            const raw = localStorage.getItem('dz_cart');
            return raw ? JSON.parse(raw) : [];
        } catch (error) {
            return [];
        }
    }

    function setCart(cart) {
        localStorage.setItem('dz_cart', JSON.stringify(Array.isArray(cart) ? cart : []));
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function escapeJsString(value) {
        return String(value || '')
            .replace(/\\/g, '\\\\')
            .replace(/'/g, "\\'");
    }

    function formatBRL(value) {
        return new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(Number(value || 0));
    }

    function itemVariant(item) {
        return String(item?.variant || item?.variacao_texto || '').trim();
    }

    function itemVariantKey(item) {
        const explicit = String(item?.variantKey || '').trim();
        if (explicit) return explicit;
        return itemVariant(item);
    }

    function itemImage(item) {
        const rawImage = String(item?.image || '').trim();
        if (!rawImage) {
            return config.fallbackImage;
        }
        return rawImage;
    }

    function subtotal() {
        return getCart().reduce(function (sum, item) {
            return sum + (Number(item?.price || 0) * Number(item?.qty || 0));
        }, 0);
    }

    function updateBadge() {
        const totalItems = getCart().reduce(function (sum, item) {
            return sum + (parseInt(item?.qty, 10) || 0);
        }, 0);

        document.querySelectorAll('#cartBadge, [data-cart-badge]').forEach(function (badge) {
            badge.textContent = String(totalItems);
            badge.style.display = totalItems > 0 ? 'flex' : 'none';
        });
    }

    function removeItem(itemId, variantKey) {
        const nextCart = getCart().filter(function (item) {
            return !(String(item?.id) === String(itemId) && String(itemVariantKey(item)) === String(variantKey || ''));
        });

        setCart(nextCart);
        sync();
    }

    function updateQty(itemId, variantKey, nextQty) {
        const cart = getCart();
        const target = cart.find(function (item) {
            return String(item?.id) === String(itemId) && String(itemVariantKey(item)) === String(variantKey || '');
        });

        if (!target) {
            return;
        }

        if (nextQty <= 0) {
            removeItem(itemId, variantKey);
            return;
        }

        target.qty = nextQty;
        setCart(cart);
        sync();
    }

    function render() {
        const body = document.getElementById('miniCartBody');
        const subtotalEl = document.getElementById('miniCartSubtotal');
        const shippingBar = document.getElementById('freeShippingBar');

        if (!body || !subtotalEl || !shippingBar) {
            console.warn('[RareMiniCart] Elementos do carrinho não encontrados');
            return;
        }

        // Garantir que freeShippingThreshold tenha um valor válido
        const threshold = Number(config.freeShippingThreshold) || 500.00;
        console.log('[RareMiniCart] Renderizando com threshold:', threshold);

        const cart = getCart();

        if (cart.length === 0) {
            body.innerHTML = '<div class="cart-empty"><div class="cart-empty-icon">🛒</div><h3>Seu carrinho está vazio</h3><p>Adicione produtos para começar.</p><button class="btn-continue-shopping" type="button" onclick="window.RareMiniCart.close()">Continuar comprando</button></div>';
            subtotalEl.textContent = formatBRL(0);

            if (threshold > 0) {
                shippingBar.innerHTML = '<div style="background:linear-gradient(135deg,#1a1a1a 0%,#0f1c2e 100%);border:1px solid rgba(198,167,94,0.2);border-radius:12px;padding:16px;margin-bottom:12px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><div style="display:flex;align-items:center;gap:8px"><span class="material-symbols-sharp" style="font-size:20px;color:#c6a75e">local_shipping</span><span style="font-size:0.75rem;color:#bfc5cc;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Frete Grátis</span></div><span style="font-size:0.9rem;color:#c6a75e;font-weight:700">R$ 0,00 / ' + formatBRL(threshold) + '</span></div><div style="height:7px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden;border:1px solid rgba(198,167,94,0.15)"><div style="height:100%;background:linear-gradient(90deg,#c6a75e 0%,#e6d1a3 100%);border-radius:inherit;transition:width 0.45s cubic-bezier(0.34,1.56,0.64,1);width:0%;box-shadow:0 0 20px rgba(198,167,94,0.4)"></div></div></div>';
            } else {
                shippingBar.innerHTML = '';
            }

            return;
        }

        body.innerHTML = cart.map(function (item) {
            const image = itemImage(item);
            const name = escapeHtml(item?.name || 'Produto');
            const variant = escapeHtml(itemVariant(item));
            const variantKey = escapeHtml(itemVariantKey(item));
            const qty = Math.max(1, parseInt(item?.qty, 10) || 1);
            const price = Number(item?.price || 0);
            const itemId = Number(item?.id || 0);

            return '<div class="cart-item">'
                + '<div class="cart-item-image"><img src="' + escapeHtml(image) + '" alt="' + name + '" loading="lazy"></div>'
                + '<div class="cart-item-details">'
                + '<div class="cart-item-name">' + name + '</div>'
                + (variant ? '<div class="cart-item-variant">' + variant + '</div>' : '')
                + '<div class="cart-item-price">' + formatBRL(price) + '</div>'
                + '<div class="cart-item-actions">'
                + '<div class="qty-control">'
                + '<button class="qty-btn" type="button" data-mini-cart-action="decrease" data-item-id="' + itemId + '" data-variant-key="' + variantKey + '" ' + (qty <= 1 ? 'disabled' : '') + '>−</button>'
                + '<span class="qty-value">' + qty + '</span>'
                + '<button class="qty-btn" type="button" data-mini-cart-action="increase" data-item-id="' + itemId + '" data-variant-key="' + variantKey + '">+</button>'
                + '</div>'
                + '<button class="btn-remove-item" type="button" data-mini-cart-action="remove" data-item-id="' + itemId + '" data-variant-key="' + variantKey + '" title="Remover produto" aria-label="Remover produto"><span class="material-symbols-sharp">delete</span></button>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('');

        body.querySelectorAll('[data-mini-cart-action]').forEach(function (button) {
            button.addEventListener('click', function () {
                const action = button.getAttribute('data-mini-cart-action') || '';
                const itemId = button.getAttribute('data-item-id') || '';
                const variantKey = button.getAttribute('data-variant-key') || '';
                const currentQty = Number(button.closest('.cart-item')?.querySelector('.qty-value')?.textContent || 1);

                if (action === 'remove') {
                    removeItem(itemId, variantKey);
                    return;
                }

                if (action === 'decrease') {
                    updateQty(itemId, variantKey, currentQty - 1);
                    return;
                }

                if (action === 'increase') {
                    updateQty(itemId, variantKey, currentQty + 1);
                }
            });
        });

        const cartSubtotal = subtotal();
        subtotalEl.textContent = formatBRL(cartSubtotal);

        if (threshold > 0) {
            const remaining = threshold - cartSubtotal;
            const progress = Math.min(100, Math.max(0, (cartSubtotal / threshold) * 100));

            console.log('[RareMiniCart] Progress:', { cartSubtotal, threshold, remaining, progress });

            if (remaining > 0) {
                shippingBar.innerHTML = '<div style="background:linear-gradient(135deg,#1a1a1a 0%,#0f1c2e 100%);border:1px solid rgba(198,167,94,0.2);border-radius:12px;padding:16px;margin-bottom:12px"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px"><div style="display:flex;align-items:center;gap:8px"><span class="material-symbols-sharp" style="font-size:20px;color:#bfc5cc">local_shipping</span><span style="font-size:0.75rem;color:#bfc5cc;font-weight:600;text-transform:uppercase;letter-spacing:0.5px">Frete Grátis</span></div><span style="font-size:0.9rem;color:#c6a75e;font-weight:700">' + formatBRL(cartSubtotal) + ' / ' + formatBRL(threshold) + '</span></div><div style="height:7px;background:rgba(255,255,255,0.06);border-radius:99px;overflow:hidden;border:1px solid rgba(198,167,94,0.15)"><div style="height:100%;background:linear-gradient(90deg,#c6a75e 0%,#e6d1a3 100%);border-radius:inherit;transition:width 0.45s cubic-bezier(0.34,1.56,0.64,1);width:' + progress + '%;box-shadow:0 0 20px rgba(198,167,94,0.4)"></div></div><div style="font-size:0.75rem;color:#bfc5cc;margin-top:8px;text-align:right">Faltam ' + formatBRL(remaining) + ' para frete grátis</div></div>';
            } else {
                shippingBar.innerHTML = '<div style="background:linear-gradient(135deg,#1a4d2e 0%,#0f2818 100%);border:1px solid rgba(52,211,153,0.3);border-radius:12px;padding:16px;margin-bottom:12px;display:flex;align-items:center;justify-content:center;gap:12px"><span class="material-symbols-sharp" style="font-size:24px;color:#34d399">check_circle</span><div style="text-align:center"><div style="font-size:0.75rem;color:#34d399;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;margin-bottom:2px">Parabéns!</div><div style="font-size:0.9rem;color:#34d399;font-weight:700">Frete Grátis Desbloqueado ✨</div></div></div>';
            }
        } else {
            shippingBar.innerHTML = '';
        }
    }

    function open() {
        const overlay = document.getElementById('miniCartOverlay');
        const drawer = document.getElementById('miniCartDrawer');

        render();
        updateBadge();

        if (overlay) overlay.classList.add('active');
        if (drawer) drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function close() {
        const overlay = document.getElementById('miniCartOverlay');
        const drawer = document.getElementById('miniCartDrawer');

        if (overlay) overlay.classList.remove('active');
        if (drawer) drawer.classList.remove('active');
        document.body.style.overflow = '';
    }

    function sync() {
        updateBadge();
        render();
    }

    function bindTriggers() {
        document.querySelectorAll('[data-open-mini-cart], #cartButton').forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();
                open();
            });
        });

        const overlay = document.getElementById('miniCartOverlay');
        const closeButton = document.getElementById('closeMiniCart');

        if (overlay) overlay.addEventListener('click', close);
        if (closeButton) closeButton.addEventListener('click', close);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                close();
            }
        });

        window.addEventListener('storage', function (event) {
            if (event.key === 'dz_cart') {
                sync();
            }
        });
    }

    window.RareMiniCart = {
        open: open,
        close: close,
        render: render,
        sync: sync,
        getCart: getCart,
        setCart: function (cart) {
            setCart(cart);
            sync();
        },
        removeItem: removeItem,
        updateQty: updateQty,
        updateBadge: updateBadge
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindTriggers();
            sync();
        });
    } else {
        bindTriggers();
        sync();
    }
})();
</script>