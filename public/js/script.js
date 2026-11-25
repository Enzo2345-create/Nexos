(function () {
  "use strict";

  const CATEGORIAS = {
    historia: "História",
    indigena: "Povos indígenas",
    educacao: "Educação antirracista",
    imigracao: "Imigração",
  };

  /* ---------- Armazenamento ---------- */
  const store = {
    get(chave, padrao) {
      try { const v = localStorage.getItem("raizes_" + chave); return v ? JSON.parse(v) : padrao; }
      catch (e) { return padrao; }
    },
    set(chave, valor) {
      try { localStorage.setItem("raizes_" + chave, JSON.stringify(valor)); } catch (e) { /* ignorado */ }
    },
  };

  /* ---------- Utilidades ---------- */
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

  function escapeHTML(texto) {
    const div = document.createElement("div");
    div.textContent = texto;
    return div.innerHTML;
  }

  function iniciais(nome) {
    return nome.replace(/[^\p{L}\s]/gu, "").trim().split(/\s+/).filter(Boolean).slice(0, 2).map((p) => p[0]).join("").toUpperCase() || "?";
  }

  function aviso(mensagem) {
    $("#avisoTexto").textContent = mensagem;
    bootstrap.Toast.getOrCreateInstance($("#aviso"), { delay: 2600 }).show();
  }

  /* ---------- Pontos (gamificação) ---------- */
  let meusPontos = Number($(".js-meus-pontos")?.textContent || 0);
  function atualizarPontos(ganho, motivo) {
    if (ganho) {
      meusPontos += ganho;
      aviso(`+${ganho} pontos! ${motivo || ""}`.trim());
    }
    $$(".js-meus-pontos").forEach((el) => (el.textContent = meusPontos));
    renderRanking();
  }

  /* ---------- Fórum ---------- */
  let topicos = [];
  const forum = $("#listaTopicos"), api = forum.dataset.api, csrf = forum.dataset.csrf, userId = Number(forum.dataset.userId || 0);
  async function request(path, data) {
    const res = await fetch(path, data ? {method:"POST",body:new URLSearchParams(data)} : {});
    const body = await res.json(); if (!res.ok) throw new Error(body.error || "A operação não foi concluída."); return body;
  }
  async function carregarTopicos() {
    try { topicos=await request(api+"/index"); renderTopicos(); }
    catch(err) { forum.innerHTML=`<p class="p-4 text-soft">${escapeHTML(err.message)} Aplique database/requisitos.sql para ativar o fórum.</p>`; }
  }

  function renderTopicos() {
    const busca = $("#buscaTopico").value.trim().toLowerCase();
    const filtro = $("#filtroTopico").value;
    const lista = topicos.filter((t) =>
      (filtro === "todos" || t.cat === filtro) &&
      (t.titulo.toLowerCase().includes(busca) || t.texto.toLowerCase().includes(busca))
    );

    $("#listaTopicos").innerHTML = lista.map((t, i) => {
      return `
        <div class="topic">
          <div class="avatar av-${(i % 4) + 1}" aria-hidden="true">${escapeHTML(iniciais(t.autor))}</div>
          <div class="flex-grow-1">
            <span class="badge-cat badge-${t.cat}">${CATEGORIAS[t.cat]}</span>
            <h4 class="mt-2">${escapeHTML(t.titulo)}</h4>
            <p class="text-soft small mb-2">${escapeHTML(t.texto)}</p>
            <div class="topic-meta d-flex flex-wrap gap-3 align-items-center">
              <span><i class="bi bi-person"></i> <a href="${forum.dataset.profileUrl}/${t.autorId}">${escapeHTML(t.autor)}</a></span>
              <span><i class="bi bi-clock"></i> ${escapeHTML(t.quando)}</span>
              <button class="btn btn-sm btn-raizes-outline" data-responder="${t.id}"><i class="bi bi-chat"></i> Ver e responder (${t.respostas})</button>
              ${userId === t.autorId ? `<button class="btn btn-sm btn-raizes-outline" data-editar="${t.id}">Editar</button><button class="btn btn-sm btn-outline-danger" data-remover="${t.id}">Remover</button>` : (document.body.dataset.role === "administrador" ? `<button class="btn btn-sm btn-outline-danger" data-remover="${t.id}">Moderar</button>` : "")}
            </div>
            <div class="topic-replies d-none mt-3" data-replies="${t.id}"><div class="d-grid gap-2" data-reply-list></div>${userId?`<form class="mt-3 d-grid gap-2" data-reply-form="${t.id}"><input type="hidden" name="csrf" value="${csrf}"><label class="visually-hidden" for="reply-${t.id}">Sua resposta</label><textarea class="form-control" id="reply-${t.id}" name="message" rows="2" maxlength="3000" required placeholder="Escreva uma resposta respeitosa"></textarea><button class="btn btn-raizes btn-sm justify-self-start" type="submit">Responder</button></form>`:`<p class="small text-soft mb-0">Entre para responder.</p>`}</div>
          </div>
          <div class="topic-stats d-none d-sm-block"><strong>${t.respostas}</strong>respostas</div>
        </div>`;
    }).join("");

    $("#semTopicos").classList.toggle("d-none", lista.length > 0);
    renderEmDebate();
  }

  /* ---------- Capa: "Em debate" (4 tópicos mais recentes) ---------- */
  function renderEmDebate() {
    if (!topicos.length) {
      $("#emDebate").innerHTML = '<p class="text-soft">Ainda não há discussões. Comece uma conversa na comunidade.</p>';
      return;
    }
    $("#emDebate").innerHTML = topicos.slice(0, 4).map((t, i) => `
      <article class="debate-item">
        <span class="debate-num">${String(i + 1).padStart(2, "0")}</span>
        <div>
          <span class="badge-cat badge-${t.cat}">${CATEGORIAS[t.cat]}</span>
          <h3><a href="#comunidade">${escapeHTML(t.titulo)}</a></h3>
          <span class="debate-meta"><i class="bi bi-chat"></i> ${t.respostas} ${t.respostas === 1 ? "resposta" : "respostas"}</span>
        </div>
      </article>`).join("");
  }

  $("#listaTopicos").addEventListener("click", async (e) => {
    const reply=e.target.closest("[data-responder]"), remove=e.target.closest("[data-remover]"), edit=e.target.closest("[data-editar]");
    try {
      if(remove) { if(confirm(document.body.dataset.role==="administrador"?"Remover esta discussão pela moderação?":"Remover sua discussão?")){await request(`${api}/removeTopic/${remove.dataset.remover}`,{csrf}); await carregarTopicos();} return; }
      if(edit) { const t=topicos.find(x=>x.id===Number(edit.dataset.editar));const title=prompt("Editar título:",t.titulo);if(title===null)return;const message=prompt("Editar mensagem:",t.texto);if(message===null)return;await request(`${api}/updateTopic/${t.id}`,{csrf,title,message});await carregarTopicos();return; }
      if(reply) { const id=reply.dataset.responder,panel=forum.querySelector(`[data-replies="${id}"]`);panel.classList.toggle("d-none");if(!panel.classList.contains("d-none")){const rows=await request(`${api}/replies/${id}`);panel.querySelector('[data-reply-list]').innerHTML=rows.map(r=>`<article class="border-start border-3 ps-3 py-1"><div class="small"><a href="${forum.dataset.profileUrl}/${r.id_usuario}">${escapeHTML(r.nome_pessoa)}</a> · ${escapeHTML(r.criado_em)}</div><p class="mb-1">${escapeHTML(r.mensagem)}</p>${userId===Number(r.id_usuario)||document.body.dataset.role==="administrador"?`${userId===Number(r.id_usuario)?`<button class="btn btn-sm btn-link p-0 me-2" data-editar-resposta="${r.id_resposta}" data-message="${encodeURIComponent(r.mensagem)}">Editar</button>`:""}<button class="btn btn-sm btn-link text-danger p-0" data-remover-resposta="${r.id_resposta}">Excluir</button>`:""}</article>`).join('')||'<p class="small text-soft">Ainda não há respostas.</p>'; } }
      const editReply=e.target.closest('[data-editar-resposta]');if(editReply){const message=prompt('Edite sua resposta:',decodeURIComponent(editReply.dataset.message));if(message===null)return;await request(`${api}/updateReply/${editReply.dataset.editarResposta}`,{csrf,message});await carregarTopicos();return;}
      const removeReply=e.target.closest('[data-remover-resposta]');if(removeReply&&confirm('Excluir esta resposta?')){await request(`${api}/removeReply/${removeReply.dataset.removerResposta}`,{csrf});removeReply.closest('article').remove();}
    } catch(err){aviso(err.message);}
  });
  forum.addEventListener('submit',async e=>{const form=e.target.closest('[data-reply-form]');if(!form)return;e.preventDefault();try{await request(`${api}/reply/${form.dataset.replyForm}`,Object.fromEntries(new FormData(form)));await carregarTopicos();}catch(err){aviso(err.message);}});

  $("#buscaTopico").addEventListener("input", renderTopicos);
  $("#filtroTopico").addEventListener("change", renderTopicos);

  $("#formTopico").addEventListener("submit", (e) => {
    e.preventDefault();
    const form = e.currentTarget;
    if (!form.checkValidity()) { form.classList.add("was-validated"); return; }
    if(!userId){aviso("Entre na sua conta para publicar.");return;}
    request(`${api}/create`,{csrf,title:$("#topicoTitulo").value.trim(),message:$("#topicoTexto").value.trim(),category:$("#topicoCategoria").value}).then(()=>{form.reset();form.classList.remove("was-validated");bootstrap.Modal.getInstance($("#modalTopico")).hide();$("#filtroTopico").value="todos";$("#buscaTopico").value="";carregarTopicos();atualizarPontos(20,"Discussão publicada.");}).catch(err=>aviso(err.message));
  });

  $("#modalTopico").addEventListener("show.bs.modal", () => {
    if(!userId) aviso("Entre para participar do fórum.");
  });

  /* ---------- Ranking ---------- */
  function renderRanking() {
    request(api + "/ranking").then(pessoas => { $("#ranking").innerHTML = pessoas.map((p, i) => `
      <div class="rank-item ${p.eu ? "fw-semibold" : ""}">
        <span class="rank-pos">${i + 1}</span>
        <div class="avatar av-${(i % 4) + 1}" style="flex-basis:36px;width:36px;height:36px;font-size:.8rem">${escapeHTML(iniciais(p.nome_pessoa))}</div>
        <span class="flex-grow-1 small">${escapeHTML(p.nome_pessoa)}</span>
        <span class="points-pill">${p.pontos}</span>
      </div>`).join("") || '<p class="text-soft small">Participe para aparecer no ranking semanal.</p>'; }).catch(()=>{});
  }

  /* ---------- Artigos: filtro e leitura ---------- */
  $("#filtroArtigos").addEventListener("click", (e) => {
    const chip = e.target.closest("[data-filter]");
    if (!chip) return;
    $$("#filtroArtigos .btn-chip").forEach((b) => b.classList.toggle("active", b === chip));
    filtrarArtigos();
  });
  function filtrarArtigos() {
    const filter=$("#filtroArtigos .btn-chip.active")?.dataset.filter||"todos", q=$("#buscaArtigo")?.value.trim().toLocaleLowerCase('pt-BR')||"";
    $$("#listaArtigos .artigo").forEach(card=>card.classList.toggle("d-none",(filter!=="todos"&&card.dataset.cat!==filter)||!(`${card.textContent} ${card.dataset.author} ${card.dataset.search}`.toLocaleLowerCase('pt-BR').includes(q))));
    $$('[data-search-content]').forEach(card=>card.classList.toggle("d-none",!(`${card.textContent} ${card.dataset.author}`.toLocaleLowerCase('pt-BR').includes(q))));
  }
  $("#buscaArtigo")?.addEventListener("input", filtrarArtigos);

  const lidos = new Set(store.get("lidos", []));
  $$("[data-artigo]").forEach((botao) => {
    botao.addEventListener("click", () => {
      const fonte = document.getElementById(botao.dataset.artigo);
      $("#modalArtigoTitulo").textContent = fonte.dataset.titulo;
      $("#modalArtigoCat").textContent = fonte.dataset.cat;
      $("#modalArtigoTexto").innerHTML = fonte.innerHTML;
      const img = $("#modalArtigoImg");
      img.referrerPolicy = "no-referrer"; img.onerror = () => img.classList.add("d-none");
      if (fonte.dataset.img) { img.src = fonte.dataset.img; img.alt = fonte.dataset.titulo; img.classList.remove("d-none"); }
      else { img.classList.add("d-none"); img.removeAttribute("src"); }
      bootstrap.Modal.getOrCreateInstance($("#modalArtigo")).show();
      const panel=$("#painelResenhas");
      if(panel && fonte.dataset.id){
        $("#resenhaArtigoId").value=fonte.dataset.id;
        carregarResenhas(fonte.dataset.id);
      }
      if (!lidos.has(fonte.id)) {
        lidos.add(fonte.id);
        store.set("lidos", Array.from(lidos));
        if(userId && fonte.dataset.id && !fonte.dataset.externo) request(`${api}/readArticle/${fonte.dataset.id}`,{csrf}).then(r=>{meusPontos=r.points;atualizarPontos(0);}).catch(()=>{});
        setTimeout(() => {if(userId)aviso("Leitura registrada.");}, 400);
      }
    });
  });

  async function carregarResenhas(id) {
    try {
      const rows=await request(`${$("#painelResenhas").dataset.api}/reviews/${id}`);
      const panel=$("#painelResenhas"), me=Number(panel.dataset.userId), admin=panel.dataset.role==="administrador";
      $("#listaResenhas").innerHTML=rows.map(r=>`<article class="p-3 rounded border"><div class="d-flex justify-content-between gap-2"><strong><a href="${panel.dataset.profileUrl}/${r.id_usuario}">${escapeHTML(r.nome_pessoa)}</a></strong><span aria-label="Média ${Number(r.media).toFixed(1)} de 5">${"★".repeat(Math.round(r.media))}${"☆".repeat(5-Math.round(r.media))} <small>${Number(r.media).toFixed(1)} (${r.votos})</small></span></div><p class="mb-2 mt-2">${escapeHTML(r.texto)}</p><div class="d-flex align-items-center gap-2"><label class="small" for="nota-${r.id_resenha}">Sua nota:</label><select class="form-select form-select-sm" style="width:auto" id="nota-${r.id_resenha}" data-nota="${r.id_resenha}" aria-label="Avalie de 1 a 5 estrelas"><option value="">Escolha</option>${[1,2,3,4,5].map(n=>`<option value="${n}">${n} estrela${n===1?"":"s"}</option>`).join("")}</select>${me===Number(r.id_usuario)||admin?`${me===Number(r.id_usuario)?`<button class="btn btn-sm btn-raizes-outline" data-editar-resenha="${r.id_resenha}" data-texto="${encodeURIComponent(r.texto)}">Editar</button>`:""}<button class="btn btn-sm btn-outline-danger" data-apagar-resenha="${r.id_resenha}">Excluir</button>`:""}</div></article>`).join("") || '<p class="small text-soft">Ainda não há resenhas para este artigo.</p>';
    } catch(err) { $("#listaResenhas").innerHTML=`<p class="small text-soft">${escapeHTML(err.message)}</p>`; }
  }
  $("#listaResenhas")?.addEventListener("change", async e=>{const s=e.target.closest("[data-nota]");if(!s||!s.value)return;try{await request(`${$("#painelResenhas").dataset.api}/rate`,{csrf:$("#painelResenhas").dataset.csrf,review_id:s.dataset.nota,rating:s.value});aviso("Avaliação salva.");}catch(err){aviso(err.message);}});
  $("#listaResenhas")?.addEventListener("click",async e=>{const b=e.target.closest("[data-apagar-resenha]");if(!b||!confirm("Excluir esta resenha?"))return;try{await request(`${$("#painelResenhas").dataset.api}/removeReview/${b.dataset.apagarResenha}`,{csrf:$("#painelResenhas").dataset.csrf});carregarResenhas($("#resenhaArtigoId").value);}catch(err){aviso(err.message);}});
  $("#listaResenhas")?.addEventListener("click",async e=>{const b=e.target.closest("[data-editar-resenha]");if(!b)return;const text=prompt("Edite sua resenha:",decodeURIComponent(b.dataset.texto));if(text===null)return;try{await request(`${$("#painelResenhas").dataset.api}/updateReview/${b.dataset.editarResenha}`,{csrf:$("#painelResenhas").dataset.csrf,text});carregarResenhas($("#resenhaArtigoId").value);}catch(err){aviso(err.message);}});
  $("#formResenha")?.addEventListener("submit",async e=>{e.preventDefault();try{await request(`${$("#painelResenhas").dataset.api}/review`,Object.fromEntries(new FormData(e.currentTarget)));e.currentTarget.reset();carregarResenhas($("#resenhaArtigoId").value);aviso("Resenha publicada.");}catch(err){aviso(err.message);}});

  /* ---------- Desafio (quiz) ---------- */
  let PERGUNTAS = [];
  let atual = 0;
  const fonteQuiz = $("#quizFonte");

  function mostrarFonteQuiz(url) {
    fonteQuiz.replaceChildren();
    if (!url) return;
    const link = document.createElement("a");
    link.href = url;
    link.target = "_blank";
    link.rel = "noopener noreferrer";
    link.textContent = "Consultar a fonte da pergunta";
    fonteQuiz.append("Fonte: ", link);
  }

  async function carregarQuiz(){
    try {
      const daily = await request(api+"/quiz");
      PERGUNTAS = daily.questions || [];
      if (daily.date) {
        const data = new Date(`${daily.date}T12:00:00`);
        $("#quizData").textContent = `Quiz de ${data.toLocaleDateString("pt-BR")}`;
      }
      if (!PERGUNTAS.length) {
        $("#quizPergunta").textContent = "Novas perguntas em breve.";
        $("#quizOpcoes").innerHTML = "";
        $("#quizFeedback").textContent = "A equipe ainda precisa publicar perguntas no painel administrativo.";
        return;
      }
      atual = 0;
      renderPergunta();
    } catch(err) {
      $("#quizPergunta").textContent = "O questionário está indisponível.";
      $("#quizFeedback").textContent = err.message;
    }
  }

  function renderPergunta() {
    const q = PERGUNTAS[atual];
    $("#quizContador").textContent = `Pergunta ${atual + 1} de ${PERGUNTAS.length}`;
    $("#quizBarra").style.width = `${((atual + 1) / PERGUNTAS.length) * 100}%`;
    $("#quizPergunta").textContent = q.p;
    $("#quizFeedback").textContent = q.answered
      ? `Você já respondeu esta pergunta e recebeu ${q.earned} ponto${q.earned === 1 ? "" : "s"}.`
      : "";
    $("#quizProxima").classList.toggle("d-none", !q.answered);
    $("#quizProxima").disabled = false;
    $("#quizProxima").innerHTML = atual < PERGUNTAS.length - 1 ? 'Próxima <i class="bi bi-arrow-right"></i>' : 'Finalizar quiz';
    mostrarFonteQuiz(q.answered ? q.source : null);
    $("#quizOpcoes").innerHTML = q.o.map((op, i) => `<button class="quiz-option" data-op="${i}" ${q.answered ? "disabled" : ""}>${escapeHTML(op)}</button>`).join("");
    if (q.answered) {
      $$("#quizOpcoes .quiz-option").forEach((b, i) => {
        if (i === q.correctChoice) b.classList.add("correct");
        if (i === q.choice && !q.correct) b.classList.add("wrong");
      });
      if (userId) {
        meusPontos = Number($(".js-meus-pontos")?.textContent || meusPontos);
        atualizarPontos(0);
      }
    }
  }

  $("#quizOpcoes").addEventListener("click", async (e) => {
    const botao = e.target.closest("[data-op]");
    if (!botao) return;
    const q = PERGUNTAS[atual];
    const escolha = Number(botao.dataset.op);
    $$("#quizOpcoes .quiz-option").forEach(b=>b.disabled=true);
    try {
      const result = await request(api+"/answer",{csrf,question:q.id,choice:escolha});
      q.answered = true;
      q.choice = result.choice ?? escolha;
      q.correct = result.correct;
      q.correctChoice = result.correctChoice;
      q.earned = result.earned;
      q.source = result.source;
      $$("#quizOpcoes .quiz-option").forEach((b,i)=>{
        if(i===result.correctChoice)b.classList.add("correct");
        if(i===q.choice&&!result.correct)b.classList.add("wrong");
      });
      if (userId) {
        meusPontos = result.points;
        atualizarPontos(0);
        $("#quizFeedback").textContent = result.alreadyAnswered
          ? `Você já tinha respondido. Seu total nesta pergunta é ${result.earned} ponto${result.earned === 1 ? "" : "s"}.`
          : result.correct
            ? `Resposta certa! Você ganhou ${result.earned} pontos.`
            : `Não foi dessa vez. Você ganhou ${result.earned} pontos por continuar tentando.`;
        if (result.earned && !result.alreadyAnswered) aviso(`+${result.earned} pontos no quiz.`);
      } else {
        q.earned = 0;
        $("#quizFeedback").textContent = result.correct
          ? "Resposta certa! Entre na sua conta para registrar pontos."
          : "Quase! A resposta foi registrada sem pontuação porque você não entrou na conta.";
      }
      mostrarFonteQuiz(result.source);
    } catch(err) {
      $$("#quizOpcoes .quiz-option").forEach(b=>b.disabled=false);
      $("#quizFeedback").textContent=err.message;
      return;
    }
    const botaoProxima = $("#quizProxima");
    botaoProxima.innerHTML = atual < PERGUNTAS.length - 1 ? 'Próxima <i class="bi bi-arrow-right"></i>' : 'Finalizar quiz';
    botaoProxima.classList.remove("d-none");
  });

  $("#quizProxima").addEventListener("click", () => {
    if (atual < PERGUNTAS.length - 1) {
      atual++;
      renderPergunta();
      return;
    }
    $("#quizPergunta").textContent = "Quiz de hoje concluído!";
    $("#quizOpcoes").replaceChildren();
    $("#quizFeedback").textContent = "Volte amanhã para responder um novo conjunto de perguntas.";
    mostrarFonteQuiz(null);
    $("#quizProxima").disabled = true;
    $("#quizProxima").textContent = "Desafio concluído";
  });

  /* ---------- Entrar (demonstração) ---------- */
  /* ---------- Navbar ao rolar ---------- */
  const navbar = $("#navbar");
  const aoRolar = () => navbar.classList.toggle("scrolled", window.scrollY > 10);
  window.addEventListener("scroll", aoRolar, { passive: true });
  aoRolar();

  // Fecha o menu no celular ao clicar em um link
  $$("#menuPrincipal .nav-link").forEach((link) => {
    link.addEventListener("click", () => {
      const menu = bootstrap.Collapse.getInstance($("#menuPrincipal"));
      if (menu) menu.hide();
    });
  });

  /* ---------- Animações de entrada e contadores ---------- */
  const observer = new IntersectionObserver((entradas) => {
    entradas.forEach((entrada) => {
      if (!entrada.isIntersecting) return;
      entrada.target.classList.add("visible");
      observer.unobserve(entrada.target);
    });
  }, { threshold: 0.12 });
  $$(".reveal").forEach((el) => observer.observe(el));

  $$("[data-count]").forEach((el) => {
    const alvo = Number(el.dataset.count);
    const formatar = (n) => (alvo > 9999 ? n.toLocaleString("pt-BR").replace(/\./g, ".") : String(n));
    let inicio = null;
    const passo = (t) => {
      if (!inicio) inicio = t;
      const progresso = Math.min((t - inicio) / 1400, 1);
      el.textContent = formatar(Math.round(alvo * (1 - Math.pow(1 - progresso, 3))));
      if (progresso < 1) requestAnimationFrame(passo);
    };
    requestAnimationFrame(passo);
  });

  /* ---------- Início ---------- */
  renderTopicos();
  carregarQuiz();
  carregarTopicos();
  atualizarPontos(0);
})();
