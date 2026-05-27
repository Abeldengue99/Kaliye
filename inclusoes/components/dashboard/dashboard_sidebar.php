<?php
/**
 * inclusoes/components/dashboard/dashboard_sidebar.php
 * Sidebar - EXACT MATCH FOR REF IMAGE
 */
?>
<div class="space-y-10">

  <!-- Status de Viabilidade -->
  <div class="glass-card shadow-2xl" style="background: rgba(255,255,255,0.02);">
    <div class="flex justify-between items-start mb-6">
      <div>
        <h4 class="text-white font-bold text-lg mb-1 leading-none">Status de Viabilidade</h4>
        <p class="text-[9px] font-bold text-white/40 tracking-widest uppercase">Análise em tempo real via KALIYE IA</p>
      </div>
      <div class="ai-pulse"></div>
    </div>

    <div class="space-y-6">
      <!-- Mercado -->
      <div>
        <div class="flex justify-between text-[9px] font-black tracking-widest uppercase mb-2">
          <span class="text-white/60">Mercado</span>
          <span class="text-[var(--aksanti-green)]">92%</span>
        </div>
        <div class="progress-container"><div class="progress-fill bg-[var(--aksanti-green)]" style="width: 92%;"></div></div>
      </div>
      <!-- Escalabilidade -->
      <div>
        <div class="flex justify-between text-[9px] font-black tracking-widest uppercase mb-2">
          <span class="text-white/60">Escalabilidade</span>
          <span class="text-[var(--aksanti-orange)]">74%</span>
        </div>
        <div class="progress-container"><div class="progress-fill bg-[var(--aksanti-orange)]" style="width: 74%;"></div></div>
      </div>
      <!-- Inovação -->
      <div>
        <div class="flex justify-between text-[9px] font-black tracking-widest uppercase mb-2">
          <span class="text-white/60">Inovação</span>
          <span class="text-[var(--aksanti-blue)]">85%</span>
        </div>
        <div class="progress-container"><div class="progress-fill bg-[var(--aksanti-blue)]" style="width: 85%;"></div></div>
      </div>
    </div>

    <button class="w-full mt-10 py-3 rounded-xl border border-white/5 bg-white/[0.02] text-[10px] font-black tracking-[0.2em] uppercase text-white hover:bg-white/5 transition-all">
      RELATÓRIO DETALHADO
    </button>
  </div>

  <!-- Promo Image Card -->
  <div class="relative rounded-3xl overflow-hidden aspect-[1.2/1] group cursor-pointer border border-white/5 shadow-2xl">
      <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&q=80&w=2071" class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110">
      <div class="absolute inset-x-0 bottom-0 p-8 bg-gradient-to-t from-[#050a15] via-[#050a15]/40 to-transparent">
          <span class="px-2 py-[2px] bg-[#f7941d] text-black text-[8px] font-black tracking-widest uppercase rounded mb-3 inline-block">DESTAQUE</span>
          <h5 class="text-lg font-bold text-white mb-2 leading-tight">Masterclass: Captação de <br>Investimento Semente</h5>
          <p class="text-[11px] text-white/50 leading-relaxed font-medium">Aprenda as estratégias que levaram 5 projetos angolanos a rondas de $+1M este ano.</p>
      </div>
  </div>

  <!-- Agenda -->
  <div class="space-y-6">
    <h4 class="text-[9px] font-black tracking-widest text-white/30 uppercase pl-1">AGENDA DE HOJE</h4>
    
    <div class="space-y-3">
      <div class="glass-card !p-5 !py-4 flex items-center border-l-4 border-l-[#f7941d] !rounded-l-none">
          <div class="flex-1">
              <div class="text-[13px] font-bold text-white mb-1">Call com Mentor Principal</div>
              <div class="text-[10px] font-medium text-white/40">15:30 • Zoom Conference</div>
          </div>
      </div>
      <div class="glass-card !p-5 !py-4 flex items-center border-l-4 border-l-[var(--aksanti-blue)] !rounded-l-none">
          <div class="flex-1">
              <div class="text-[13px] font-bold text-white mb-1">Pitch Desk Review</div>
              <div class="text-[10px] font-medium text-white/40">17:00 • Escritório KALIYE</div>
          </div>
      </div>
    </div>
  </div>

</div>
