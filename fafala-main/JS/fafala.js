const Fafala = (function () {
  let modoEdicao = false;

  function el(tag, atributos, filhos) {
    const e = document.createElement(tag);
    Object.entries(atributos || {}).forEach(function ([k, v]) {
      if (k === "class") e.className = v;
      else if (k === "text") e.textContent = v;
      else e.setAttribute(k, v);
    });
    (filhos || []).forEach(function (f) {
      e.appendChild(f);
    });
    return e;
  }

  async function api(acao, parametros, dadosPost) {
    const url =
      "/fafala/PHP/tecnico/api.php?acao=" + acao + "&" + new URLSearchParams(parametros || {});
    const resposta = await fetch(
      url,
      dadosPost ? { method: "POST", body: dadosPost } : undefined,
    );
    let json = {};
    try {
      json = await resposta.json();
    } catch (e) {
      /* resposta sem JSON */
    }
    if (!resposta.ok)
      throw new Error(
        json.erro || "Erro ao falar com o servidor (" + resposta.status + ").",
      );
    return json;
  }

  function imagemOuVazio(imagemId, nome) {
    if (imagemId)
      return el("img", { src: "/fafala/PHP/tecnico/imagem.php?id=" + imagemId, alt: nome });
    return el("div", {
      class: "sem-imagem",
      text: nome.charAt(0).toUpperCase(),
    });
  }

  async function prepararImagem(arquivo) {
    if (arquivo.type === "image/gif") return arquivo;
    try {
      const bmp = await createImageBitmap(arquivo);
      const escala = Math.min(1, 800 / Math.max(bmp.width, bmp.height));
      if (escala === 1 && arquivo.size < 500 * 1024) return arquivo;
      const canvas = document.createElement("canvas");
      canvas.width = Math.round(bmp.width * escala);
      canvas.height = Math.round(bmp.height * escala);
      canvas.getContext("2d").drawImage(bmp, 0, 0, canvas.width, canvas.height);
      const blob = await new Promise(function (ok) {
        canvas.toBlob(ok, "image/webp", 0.85);
      });
      return blob && blob.size < arquivo.size ? blob : arquivo;
    } catch (e) {
      return arquivo;
    }
  }

  let dialogo = null;

  function criarDialogo() {
    const d = el("dialog", { class: "modal" });
    d.innerHTML =
      '<form class="modal-form">' +
      '<h2 class="modal-titulo"></h2>' +
      '<label class="modal-imagem">' +
      '<span class="modal-previa"></span>' +
      '<span class="modal-trocar">Escolher imagem</span>' +
      '<input type="file" name="arquivo" accept="image/*" hidden>' +
      "</label>" +
      '<label>Nome <input type="text" name="nome" maxlength="100" required autocomplete="off"></label>' +
      '<p class="modal-erro" role="alert"></p>' +
      '<div class="modal-botoes">' +
      '<button type="button" class="btn btn-excluir">Excluir</button>' +
      '<span class="espaco"></span>' +
      '<button type="button" class="btn btn-cancelar">Cancelar</button>' +
      '<button type="submit" class="btn btn-salvar">Salvar</button>' +
      "</div>" +
      "</form>";
    document.body.appendChild(d);
    d.addEventListener("click", function (e) {
      if (e.target === d) d.close();
    });
    d.querySelector(".btn-cancelar").addEventListener("click", function () {
      d.close();
    });
    return d;
  }

  function abrirFormulario(opcoes) {
    if (!dialogo) dialogo = criarDialogo();
    const d = dialogo;
    const form = d.querySelector("form");
    const campoNome = form.elements["nome"];
    const campoArquivo = form.elements["arquivo"];
    const previa = d.querySelector(".modal-previa");
    const erro = d.querySelector(".modal-erro");
    const btnSalvar = d.querySelector(".btn-salvar");
    const btnExcluir = d.querySelector(".btn-excluir");

    d.querySelector(".modal-titulo").textContent = opcoes.titulo;
    campoNome.value = opcoes.nome || "";
    campoArquivo.value = "";
    erro.textContent = "";
    btnSalvar.disabled = false;
    previa.replaceChildren(imagemOuVazio(opcoes.imagemId, opcoes.nome || "+"));
    btnExcluir.hidden = !opcoes.aoExcluir;

    campoArquivo.onchange = function () {
      const arq = campoArquivo.files[0];
      if (arq)
        previa.replaceChildren(
          el("img", { src: URL.createObjectURL(arq), alt: "pré-visualização" }),
        );
    };

    form.onsubmit = async function (e) {
      e.preventDefault();
      btnSalvar.disabled = true;
      erro.textContent = "";
      try {
        const dados = new FormData();
        dados.append("nome", campoNome.value.trim());
        if (campoArquivo.files[0]) {
          const pronta = await prepararImagem(campoArquivo.files[0]);
          dados.append("imagem", pronta, "imagem");
        }
        await opcoes.aoSalvar(dados);
        d.close();
      } catch (ex) {
        erro.textContent = ex.message;
        btnSalvar.disabled = false;
      }
    };

    btnExcluir.onclick = async function () {
      if (!confirm(opcoes.mensagemExcluir || "Excluir este item?")) return;
      try {
        await opcoes.aoExcluir();
        d.close();
      } catch (ex) {
        erro.textContent = ex.message;
      }
    };

    d.showModal();
    campoNome.focus();
  }

  function iniciarBotaoEditar() {
    const botao = document.getElementById("botao-editar");
    botao.addEventListener("click", function () {
      modoEdicao = !modoEdicao;
      document.body.classList.toggle("modo-edicao", modoEdicao);
      botao.textContent = modoEdicao ? "Concluir" : "Editar";
      botao.setAttribute("aria-pressed", modoEdicao);
    });
  }

  function blocoAdicionar(classe, rotulo, aoClicar) {
    const bloco = el(
      "div",
      {
        class: classe + " adicionar",
        role: "button",
        tabindex: "0",
        "aria-label": rotulo,
      },
      [el("div", { class: "mais", text: "+" }), el("h3", { text: rotulo })],
    );
    bloco.addEventListener("click", aoClicar);
    bloco.addEventListener("keydown", function (e) {
      if (e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        aoClicar();
      }
    });
    return bloco;
  }

  return {
    el: el,
    api: api,
    imagemOuVazio: imagemOuVazio,
    abrirFormulario: abrirFormulario,
    iniciarBotaoEditar: iniciarBotaoEditar,
    blocoAdicionar: blocoAdicionar,
    emEdicao: function () {
      return modoEdicao;
    },
  };
})();
