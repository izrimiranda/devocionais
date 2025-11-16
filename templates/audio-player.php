<?php
/**
 * Template: Audio Player
 * Player de áudio customizado
 * 
 * Variável esperada:
 * - $audio: caminho do arquivo de áudio
 */

if (!isset($audio) || !$audio) {
    return;
}
?>

<div class="audio-player">
    <div class="player-header">
        <svg class="player-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path d="M9 18V5l12-2v13M9 13l12-2" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <span class="player-label">Áudio do Devocional</span>
    </div>
    
    <audio 
        controls 
        preload="metadata"
        class="audio-element"
    >
        <source src="<?= htmlspecialchars($audio) ?>" type="audio/mpeg">
        <source src="<?= htmlspecialchars($audio) ?>" type="audio/ogg">
        Seu navegador não suporta o elemento de áudio.
    </audio>
    
    <p class="player-help">
        <small>Clique no play para ouvir o devocional</small>
    </p>
</div>
