/**
 * Script para atualizaÃ§Ã£o automÃ¡tica das mensagens na pÃ¡gina de chat
 */

const __noopLog = (...args) => {};

let ultimaMensagemId = 0;
let conversaAtiva = null;
let atualizandoMensagens = false;
let atualizandoConversas = false;

// FunÃ§Ã£o global para ser chamada quando uma conversa Ã© selecionada
window.definirConversaAtiva = function (conversaId) {
  __noopLog("ðŸŽ¯ Conversa ativa definida:", conversaId);
  conversaAtiva = conversaId;
  ultimaMensagemId = 0; // Reset para carregar todas as mensagens
};

// FunÃ§Ã£o global para verificar mensagens (chamada pelo sistema existente)
window.verificarMensagensConversa = function (conversaId) {
  __noopLog("ðŸ” Verificando mensagens da conversa:", conversaId);

  const url = `api-mensagens.php?conversa_id=${conversaId}&ultima_id=0`;

  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      __noopLog("ðŸ“¨ Resposta da API:", data);

      if (data.success && data.mensagens.length > 0) {
        __noopLog(
          `âœ… Conversa ${conversaId} tem ${data.mensagens.length} mensagens`,
        );

        // Verificar se a Ã¡rea de mensagens estÃ¡ visÃ­vel
        const chatArea = document.querySelector("#mensagens-container");
        if (!chatArea) {
          __noopLog("âŒ Ãrea de mensagens nÃ£o encontrada");
          return;
        }

        // Verificar mensagens que nÃ£o estÃ£o na tela
        const mensagensNaTela = document.querySelectorAll("[data-message-id]");
        const idsNaTela = Array.from(mensagensNaTela).map((m) =>
          parseInt(m.getAttribute("data-message-id")),
        );

        let novasMensagens = 0;
        data.mensagens.forEach((mensagem) => {
          if (!idsNaTela.includes(mensagem.id)) {
            __noopLog("âž• Adicionando nova mensagem:", mensagem.conteudo);
            adicionarMensagemAoChat(mensagem);
            novasMensagens++;
          }
        });

        if (novasMensagens > 0) {
          __noopLog(`ðŸŽ‰ ${novasMensagens} mensagens novas adicionadas!`);
          // Fazer scroll para baixo
          setTimeout(() => {
            chatArea.scrollTop = chatArea.scrollHeight;
          }, 100);
        } else {
          __noopLog("â„¹ï¸ Nenhuma mensagem nova encontrada");
        }
      } else {
        __noopLog("âš ï¸ Nenhuma mensagem retornada pela API");
      }
    })
    .catch((error) => {
      __noopLog("âŒ Erro ao verificar mensagens:", error);
    });
};

// FunÃ§Ã£o para detectar conversa ativa automaticamente
function detectarConversaAtiva() {
  // EstratÃ©gia 1: Verificar se hÃ¡ uma conversa visÃ­vel
  const conversaVisivel = document.querySelector("#conversa-ativa");
  if (conversaVisivel && conversaVisivel.style.display !== "none") {
    // EstratÃ©gia 2: Procurar por URL parameter primeiro
    const urlParams = new URLSearchParams(window.location.search);
    const conversaIdUrl = urlParams.get("conversa_id");
    if (conversaIdUrl) {
      __noopLog("Conversa detectada via URL:", conversaIdUrl);
      conversaAtiva = conversaIdUrl;
      return conversaAtiva;
    }

    // EstratÃ©gia 3: Procurar conversa com classe ativa
    const conversaAtivaSidebar = document.querySelector(
      ".conversation-item.active, .conversation-item.selected",
    );
    if (conversaAtivaSidebar) {
      const novoId = conversaAtivaSidebar.getAttribute("data-id");
      if (novoId) {
        __noopLog("Conversa detectada via classe ativa:", novoId);
        conversaAtiva = novoId;
        return conversaAtiva;
      }
    }

    // EstratÃ©gia 4: Usar window.conversaAtual se definida globalmente
    if (window.conversaAtual) {
      __noopLog(
        "Conversa detectada via variÃ¡vel global:",
        window.conversaAtual,
      );
      conversaAtiva = window.conversaAtual;
      return conversaAtiva;
    }
  }

  __noopLog("Nenhuma conversa ativa detectada");
  return conversaAtiva;
}

// FunÃ§Ã£o para atualizar mensagens - versÃ£o mais robusta
function atualizarMensagens() {
  if (atualizandoMensagens) return;

  atualizandoMensagens = true;

  // Detectar conversa ativa
  const conversaId = detectarConversaAtiva() || conversaAtiva;

  // Se nÃ£o encontrou conversa ativa, tentar todas as conversas visÃ­veis
  if (!conversaId) {
    __noopLog("Tentando atualizar todas as conversas visÃ­veis");
    atualizarTodasConversasVisiveis();
    atualizandoMensagens = false;
    return;
  }

  __noopLog("Atualizando mensagens da conversa:", conversaId);

  const url = `api-mensagens.php?conversa_id=${conversaId}&ultima_id=${ultimaMensagemId}`;

  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      __noopLog("Resposta das mensagens:", data);
      if (data.success && data.mensagens.length > 0) {
        const chatArea = document.querySelector("#mensagens-container");
        if (!chatArea) {
          __noopLog("Ãrea de mensagens nÃ£o encontrada");
          return;
        }

        const scrollToBottom =
          chatArea.scrollTop >=
          chatArea.scrollHeight - chatArea.clientHeight - 100;

        // Adicionar novas mensagens
        data.mensagens.forEach((mensagem) => {
          __noopLog("Adicionando mensagem:", mensagem);
          adicionarMensagemAoChat(mensagem);
          ultimaMensagemId = Math.max(ultimaMensagemId, mensagem.id);
        });

        // Fazer scroll para baixo se estava prÃ³ximo do final
        if (scrollToBottom) {
          setTimeout(() => {
            chatArea.scrollTop = chatArea.scrollHeight;
          }, 100);
        }

        // Marcar mensagens como lidas se nÃ£o sÃ£o do admin
        marcarMensagensComoLidas();

        // Atualizar contador da conversa
        atualizarContadorConversa();
      }
    })
    .catch((error) => {
      __noopLog("Erro ao atualizar mensagens:", error);
    })
    .finally(() => {
      atualizandoMensagens = false;
    });
}

// FunÃ§Ã£o para atualizar todas as conversas visÃ­veis quando nÃ£o detecta uma especÃ­fica
function atualizarTodasConversasVisiveis() {
  __noopLog("Verificando conversas visÃ­veis para atualizaÃ§Ã£o");

  // Buscar todas as conversas que tÃªm mensagens nÃ£o lidas
  const conversasComMensagens = document.querySelectorAll(
    '.conversation-item[data-nao-lidas]:not([data-nao-lidas="0"])',
  );

  conversasComMensagens.forEach((conversa) => {
    const conversaId = conversa.getAttribute("data-id");
    if (conversaId) {
      __noopLog("Atualizando conversa:", conversaId);
      // ForÃ§a uma verificaÃ§Ã£o para esta conversa
      forcarAtualizacaoConversa(conversaId);
    }
  });
}

// FunÃ§Ã£o para forÃ§ar atualizaÃ§Ã£o de uma conversa especÃ­fica
function forcarAtualizacaoConversa(conversaId) {
  const url = `api-mensagens.php?conversa_id=${conversaId}&ultima_id=0`;

  fetch(url)
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.mensagens.length > 0) {
        __noopLog(
          `Conversa ${conversaId} tem ${data.mensagens.length} mensagens`,
        );

        // Se esta conversa estÃ¡ ativa no momento, adicionar as mensagens
        const chatArea = document.querySelector("#mensagens-container");
        const conversaVisivel = document.querySelector("#conversa-ativa");

        if (
          chatArea &&
          conversaVisivel &&
          conversaVisivel.style.display !== "none"
        ) {
          // Verificar se Ã© a conversa atual baseada no contexto
          const mensagemMaisRecente = data.mensagens[data.mensagens.length - 1];
          if (
            mensagemMaisRecente &&
            !document.querySelector(
              `[data-message-id="${mensagemMaisRecente.id}"]`,
            )
          ) {
            __noopLog(
              "Adicionando mensagem da conversa forÃ§ada:",
              mensagemMaisRecente.conteudo,
            );
            adicionarMensagemAoChat(mensagemMaisRecente);
          }
        }
      }
    })
    .catch((error) => {
      __noopLog("Erro ao forÃ§ar atualizaÃ§Ã£o da conversa:", error);
    });
}

// FunÃ§Ã£o para atualizar lista de conversas
function atualizarConversas() {
  if (atualizandoConversas) return;

  atualizandoConversas = true;

  __noopLog("Buscando conversas atualizadas...");
  fetch("api-conversas.php")
    .then((response) => response.json())
    .then((data) => {
      __noopLog("Dados recebidos:", data);
      if (data.success) {
        // Atualizar contadores nos filtros
        atualizarContadoresFiltros(data.stats);

        // Atualizar indicadores visuais nas conversas
        atualizarIndicadoresConversas(data.conversas);
      }
    })
    .catch((error) => {
      __noopLog("Erro ao atualizar conversas:", error);
    })
    .finally(() => {
      atualizandoConversas = false;
    });
}

// FunÃ§Ã£o para atualizar contadores dos filtros
function atualizarContadoresFiltros(stats) {
  const contadores = {
    todas: stats.total,
    nao_lidas: stats.nao_lidas,
    ativa: stats.ativas,
    aguardando_humano: stats.aguardando_humano,
    resolvida: stats.resolvidas,
  };

  Object.keys(contadores).forEach((filtro) => {
    const elemento = document.querySelector(`[onclick*="'${filtro}'"] .count`);
    if (elemento) {
      elemento.textContent = contadores[filtro];
    }
  });
}

// FunÃ§Ã£o para atualizar indicadores visuais das conversas
function atualizarIndicadoresConversas(conversas) {
  conversas.forEach((conversa) => {
    const item = document.querySelector(`[data-id="${conversa.id}"]`);
    if (item) {
      // Atualizar atributos
      item.setAttribute("data-nao-lidas", conversa.nao_lidas);
      item.setAttribute("data-status", conversa.status);

      // Atualizar indicador de nÃ£o lidas
      const indicator = item.querySelector(".unread-indicator");
      if (conversa.nao_lidas > 0 && !indicator) {
        // Adicionar indicador se nÃ£o existe
        const avatar = item.querySelector(".conversation-avatar");
        if (avatar) {
          const newIndicator = document.createElement("div");
          newIndicator.className = "unread-indicator";
          avatar.appendChild(newIndicator);

          // Animar apariÃ§Ã£o
          newIndicator.style.transform = "scale(0)";
          setTimeout(() => {
            newIndicator.style.transform = "scale(1)";
            newIndicator.style.transition = "transform 0.2s ease";
          }, 10);
        }
      } else if (conversa.nao_lidas === 0 && indicator) {
        // Remover indicador se nÃ£o hÃ¡ mensagens nÃ£o lidas
        indicator.style.transform = "scale(0)";
        setTimeout(() => indicator.remove(), 200);
      }

      // Atualizar preview da Ãºltima mensagem se fornecido
      if (conversa.ultima_mensagem) {
        const preview = item.querySelector(".conversation-preview");
        if (preview) {
          preview.textContent =
            conversa.ultima_mensagem.substring(0, 40) + "...";
        }
      }

      // Atualizar timestamp
      if (conversa.updated_at) {
        const timeElement = item.querySelector(".conversation-time");
        if (timeElement) {
          const time = new Date(conversa.updated_at);
          timeElement.textContent = time.toLocaleTimeString("pt-BR", {
            hour: "2-digit",
            minute: "2-digit",
          });
        }
      }
    }
  });
}

// FunÃ§Ã£o para adicionar mensagem ao chat
function adicionarMensagemAoChat(mensagem) {
  const chatArea = document.querySelector("#mensagens-container");
  if (!chatArea) {
    __noopLog("Ãrea de mensagens nÃ£o encontrada");
    return;
  }

  // Verificar se a mensagem jÃ¡ existe
  if (document.querySelector(`[data-message-id="${mensagem.id}"]`)) {
    __noopLog("Mensagem jÃ¡ existe:", mensagem.id);
    return;
  }

  __noopLog("Adicionando nova mensagem ao chat:", mensagem.conteudo);

  const messageDiv = document.createElement("div");
  messageDiv.className = `message-bubble ${
    mensagem.remetente === "admin"
      ? "admin"
      : mensagem.remetente === "usuario"
        ? "client"
        : "ia"
  }`;
  messageDiv.setAttribute("data-message-id", mensagem.id);

  const timestamp = new Date(mensagem.timestamp).toLocaleTimeString("pt-BR", {
    hour: "2-digit",
    minute: "2-digit",
  });

  const avatar =
    mensagem.remetente === "admin"
      ? '<img src="../../../assets/images/logo.png" alt="Admin">'
      : mensagem.remetente.charAt(0).toUpperCase();

  messageDiv.innerHTML = `
    <div class="message-avatar">
      ${avatar}
    </div>
    <div class="message-content">
      <p>${mensagem.conteudo}</p>
      <span class="message-time">${timestamp}</span>
    </div>
  `;

  // Animar entrada da nova mensagem
  messageDiv.style.opacity = "0";
  messageDiv.style.transform = "translateY(20px)";
  messageDiv.style.background = "#f0f0f0";

  chatArea.appendChild(messageDiv);

  // Animar entrada
  setTimeout(() => {
    messageDiv.style.transition = "all 0.3s ease";
    messageDiv.style.opacity = "1";
    messageDiv.style.transform = "translateY(0)";

    // Remover destaque apÃ³s animaÃ§Ã£o
    setTimeout(() => {
      messageDiv.style.background = "";
    }, 2000);
  }, 10);

  __noopLog("Mensagem adicionada com sucesso!");
}

// FunÃ§Ã£o para marcar mensagens como lidas
function marcarMensagensComoLidas() {
  if (!conversaAtiva) return;

  fetch("../sistema.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `action=marcar_como_lidas&conversa_id=${conversaAtiva}`,
  }).catch((error) => {
    __noopLog("Erro ao marcar como lidas:", error);
  });
}

// FunÃ§Ã£o para atualizar contador da conversa especÃ­fica
function atualizarContadorConversa() {
  if (!conversaAtiva) return;

  const conversaItem = document.querySelector(`[data-id="${conversaAtiva}"]`);
  if (conversaItem) {
    const indicator = conversaItem.querySelector(".unread-indicator");
    if (indicator) {
      indicator.remove(); // Remove o indicador de nÃ£o lidas
    }
    conversaItem.setAttribute("data-nao-lidas", "0");
  }
}

// FunÃ§Ã£o para inicializar uma conversa
function inicializarConversa(conversaId) {
  conversaAtiva = conversaId;

  // Encontrar a Ãºltima mensagem para saber de onde continuar
  const mensagens = document.querySelectorAll("[data-message-id]");
  ultimaMensagemId = 0;
  mensagens.forEach((msg) => {
    const id = parseInt(msg.getAttribute("data-message-id"));
    if (id > ultimaMensagemId) {
      ultimaMensagemId = id;
    }
  });

  // ComeÃ§ar a atualizar mensagens
  atualizarMensagens();
}

// Inicializar quando o DOM estiver pronto
document.addEventListener("DOMContentLoaded", function () {
  // Verificar se estamos na pÃ¡gina de mensagens
  if (window.location.pathname.includes("menssage.php")) {
    // Detectar clique em conversas para inicializar
    document.addEventListener("click", function (e) {
      const conversaItem = e.target.closest(".conversation-item[data-id]");
      if (conversaItem) {
        const conversaId = conversaItem.getAttribute("data-id");
        inicializarConversa(conversaId);
      }
    });

    // Se jÃ¡ hÃ¡ uma conversa ativa (detectar pela URL ou elemento ativo)
    const conversaAtual = document.querySelector(
      ".conversa-ativa, .active-conversation",
    );
    if (conversaAtual) {
      const conversaId =
        conversaAtual.getAttribute("data-conversa-id") ||
        new URLSearchParams(window.location.search).get("conversa_id");
      if (conversaId) {
        inicializarConversa(conversaId);
      }
    }

    // Atualizar conversas imediatamente
    atualizarConversas();

    // Tentar detectar conversa ativa imediatamente
    setTimeout(() => {
      detectarConversaAtiva();
      if (conversaAtiva) {
        atualizarMensagens();
      }
    }, 1000);

    // FunÃ§Ã£o simples que sempre tenta atualizar
    function verificarMensagensNovas() {
      __noopLog("Verificando mensagens novas...");

      // Se hÃ¡ uma Ã¡rea de mensagens visÃ­vel, tentar atualizar
      const chatArea = document.querySelector("#mensagens-container");
      const conversaVisivel = document.querySelector("#conversa-ativa");

      if (
        chatArea &&
        conversaVisivel &&
        conversaVisivel.style.display !== "none"
      ) {
        // Tentar diferentes estratÃ©gias para encontrar a conversa
        let conversaId = null;

        // 1. URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        conversaId = urlParams.get("conversa_id");

        // 2. VariÃ¡vel global
        if (!conversaId && window.conversaAtual) {
          conversaId = window.conversaAtual;
        }

        // 3. Conversa ativa na sidebar
        if (!conversaId) {
          const ativa = document.querySelector(
            ".conversation-item.active[data-id], .conversation-item.selected[data-id]",
          );
          if (ativa) {
            conversaId = ativa.getAttribute("data-id");
          }
        }

        // 4. Primeira conversa com mensagens nÃ£o lidas
        if (!conversaId) {
          const comMensagens = document.querySelector(
            '.conversation-item[data-nao-lidas]:not([data-nao-lidas="0"])',
          );
          if (comMensagens) {
            conversaId = comMensagens.getAttribute("data-id");
          }
        }

        if (conversaId) {
          __noopLog("Verificando mensagens para conversa:", conversaId);
          verificarMensagensConversa(conversaId);
        } else {
          __noopLog("Nenhuma conversa encontrada para verificar");
        }
      }
    }

    // FunÃ§Ã£o para verificar mensagens de uma conversa especÃ­fica
    function verificarMensagensConversa(conversaId) {
      const url = `api-mensagens.php?conversa_id=${conversaId}&ultima_id=0`;

      fetch(url)
        .then((response) => response.json())
        .then((data) => {
          if (data.success && data.mensagens.length > 0) {
            __noopLog(
              `Conversa ${conversaId} tem ${data.mensagens.length} mensagens`,
            );

            // Verificar se hÃ¡ mensagens novas que nÃ£o estÃ£o na tela
            const mensagensNaTela =
              document.querySelectorAll("[data-message-id]");
            const idsNaTela = Array.from(mensagensNaTela).map((m) =>
              parseInt(m.getAttribute("data-message-id")),
            );

            data.mensagens.forEach((mensagem) => {
              if (!idsNaTela.includes(mensagem.id)) {
                __noopLog("Nova mensagem encontrada:", mensagem.conteudo);
                adicionarMensagemAoChat(mensagem);
              }
            });
          }
        })
        .catch((error) => __noopLog("Erro ao verificar mensagens:", error));
    }

    // Atualizar mensagens a cada 2 segundos usando a nova funÃ§Ã£o
    setInterval(verificarMensagensNovas, 2000);

    // Atualizar conversas a cada 4 segundos
    setInterval(atualizarConversas, 4000);

    // Atualizar quando a janela ganha foco
    window.addEventListener("focus", function () {
      detectarConversaAtiva();
      atualizarMensagens();
      atualizarConversas();
    });

    // Observar mudanÃ§as no DOM para detectar quando uma conversa Ã© aberta
    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        if (
          mutation.type === "attributes" &&
          mutation.attributeName === "style"
        ) {
          const conversaVisivel = document.querySelector("#conversa-ativa");
          if (conversaVisivel && conversaVisivel.style.display !== "none") {
            detectarConversaAtiva();
            atualizarMensagens();
          }
        }
      });
    });

    const conversaContainer = document.querySelector("#conversa-ativa");
    if (conversaContainer) {
      observer.observe(conversaContainer, {
        attributes: true,
        attributeFilter: ["style"],
      });
    }
  }
});

// Sistema simples de monitoramento contÃ­nuo
setInterval(() => {
  if (conversaAtiva) {
    __noopLog("â° VerificaÃ§Ã£o automÃ¡tica - Conversa ativa:", conversaAtiva);
    if (window.verificarMensagensConversa) {
      window.verificarMensagensConversa(conversaAtiva);
    }
  }
}, 3000); // A cada 3 segundos
