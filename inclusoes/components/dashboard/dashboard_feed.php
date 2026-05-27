<?php
/**
 * inclusoes/components/dashboard/dashboard_feed.php
 * Feed Section - EXACT MATCH FOR REF IMAGE
 */
?>
<div class="space-y-10">

  <!-- POST COMPOSER -->
  <div class="glass-card !bg-white/[0.02] border-white/5">
    <div class="flex items-start gap-4">
      <div class="w-10 h-10 rounded-full overflow-hidden flex-shrink-0">
        <img alt="O teu avatar"
             class="w-full h-full object-cover"
             src="<?php echo (strpos($final_pic, 'http') === 0) ? $final_pic : $base_url . $final_pic; ?>"
             onerror="this.src='<?php echo $base_url; ?>recursos/images/default_profile.png'">
      </div>
      <div class="flex-1">
        <textarea
          class="w-full text-lg bg-transparent border-none focus:ring-0 outline-none placeholder:text-white/20 text-white resize-none"
          placeholder="Qual é a sua ideia inovadora hoje?"
          rows="1"
          onclick="window.openPostModal()"></textarea>
        
        <div class="flex justify-between items-center mt-6 pt-6 border-t border-white/5">
          <div class="flex gap-6 text-white/40">
             <button class="hover:text-white transition-colors" onclick="window.openPostModal()"><span class="material-symbols-outlined">image</span></button>
             <button class="hover:text-white transition-colors" onclick="window.openPostModal()"><span class="material-symbols-outlined">attach_file</span></button>
             <button class="hover:text-white transition-colors" onclick="window.openPostModal()"><span class="material-symbols-outlined">location_on</span></button>
          </div>
          <button class="px-6 py-2.5 bg-[#f7941d] text-black font-bold rounded-xl text-sm hover:scale-105 transition-transform"
                  onclick="window.openPostModal()">
            Publicar Ideia
          </button>
        </div>
      </div>
    </div>
  </div>

  <div class="flex justify-between items-center px-1">
    <h3 class="text-xl font-bold tracking-tight">O seu feed está em órbita</h3>
    <button class="text-[10px] font-black tracking-widest text-[#f7941d] uppercase flex items-center gap-2">
        FILTRAR <span class="material-symbols-outlined text-sm">filter_list</span>
    </button>
  </div>

  <!-- FEED POSTS -->
  <div id="posts-container" class="space-y-8">
    
    <!-- Intelligence Post (Mock from Ref) -->
    <div class="glass-card shadow-lg" data-aos="fade-up">
      <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center border border-white/5">
            <span class="material-symbols-outlined text-[var(--aksanti-blue)]">hub</span>
        </div>
        <div>
            <div class="text-sm font-bold">KALIYE Intelligence</div>
            <div class="text-[9px] font-bold text-[#f7941d] tracking-widest uppercase">AGORA MESMO • TENDÊNCIA EM LUANDA</div>
        </div>
      </div>
      <p class="text-white/70 text-[14px] leading-relaxed mb-6 font-normal">
        Observamos um aumento de 45% no interesse por tecnologias sustentáveis em zonas peri-urbanas. Abel, o seu projeto "Eco-Filtro" está perfeitamente alinhado com esta tendência.
      </p>
      <div class="rounded-xl overflow-hidden aspect-[16/7] relative">
          <img src="<?php echo $base_url; ?>recursos/images/anuncios/trend_viz.png" class="w-full h-full object-cover" 
               onerror="this.src='https://images.unsplash.com/photo-1451187580459-43490279c0fa?auto=format&fit=crop&q=80&w=2072'">
          <div class="absolute inset-0 bg-gradient-to-t from-black/80 to-transparent"></div>
          <div class="absolute inset-0 border border-white/5 rounded-xl"></div>
      </div>
    </div>

    <!-- Parceria Premium (Mock from Ref) -->
    <div class="glass-card" data-aos="fade-up" data-aos-delay="100">
      <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-white/5 flex items-center justify-center border border-white/5">
            <span class="material-symbols-outlined text-[#f7941d]">stars</span>
        </div>
        <div>
            <div class="text-sm font-bold">Parceria Premium</div>
            <div class="text-[9px] font-bold text-[#f7941d] tracking-widest uppercase">HÁ 2 HORAS • EVENTO EXCLUSIVO</div>
        </div>
      </div>
      <p class="text-white/70 text-[14px] leading-relaxed font-normal">
        O convite para o "Angola Innovation Summit" foi enviado para o seu email. Verifique a aba de mentorias para RSVP.
      </p>
    </div>

    <!-- PROJECT LOOP (3-Column Grid) -->
    <div class="projects-grid">
        <?php while ($post = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
            <?php include 'inclusoes/components/post_card.php'; ?>
        <?php endwhile; ?>
    </div>

  </div>

</div>
