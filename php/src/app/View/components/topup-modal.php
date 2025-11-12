<?php

?>

<!-- Unified Topup Modal -->
<div id="unifiedTopupModal" class="topup-modal">
    <div class="topup-modal-content">
        <!-- Modal Header -->
        <div class="topup-modal-header">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"/>
                <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"/>
                <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/>
            </svg>
            Top-up Saldo
        </div>
        
        <!-- Modal Body -->
        <div class="topup-modal-body">
            <!-- Quick Amount Buttons -->
            <div class="topup-quick-amounts">
            </div>
            
            <!-- Custom Amount Input -->
            <input 
                type="number" 
                class="topup-input" 
                placeholder="Atau masukkan nominal manual"
                min="10000"
                step="10000"
            >
        </div>
        
        <!-- Modal Actions -->
        <div class="topup-modal-actions">
            <button type="button" class="topup-btn topup-btn-cancel" onclick="closeTopupModal()">
                Batal
            </button>
            <button type="button" class="topup-btn topup-btn-confirm" onclick="processTopup()" disabled>
                Top-up
            </button>
        </div>
    </div>
</div>

<!-- Topup Modal Styles -->
<link rel="stylesheet" href="/css/components/topup-modal.css">
<!-- Topup Modal Script -->
<script src="/js/components/topup-modal.js"></script>