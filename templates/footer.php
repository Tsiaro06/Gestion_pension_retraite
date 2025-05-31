</main>
        
        <!-- <footer class="footer">
            <div class="container">
                <p>© <?php echo date('Y'); ?> Système de Gestion des Pensions. Tous droits réservés.</p>
            </div>
        </footer>
    </div> -->

    <!-- Toast notifications -->
    <div id="toast-container"></div>

    <!-- Modal container -->
    <div id="modal-container" class="modal-container hidden">
        <div class="modal-backdrop"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Titre du modal</h3>
                <button id="modal-close" class="modal-close-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>
            </div>
            <div id="modal-body" class="modal-body">
                <!-- Contenu du modal -->
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- <script src="assets/js/utils.js"></script> -->
    <script src="assets/js/main.js"></script>
    
    <!-- <?php if ($activeTab === 'dashboard'): ?> -->
    <!-- <script src="assets/js/dashboard.js"></script> -->
    <?php elseif ($activeTab === 'personnes'): ?>
    <script src="assets/js/personnes.js"></script>
    <?php elseif ($activeTab === 'tarifs'): ?>
    <script src="assets/js/tarifs.js"></script>
    <?php elseif ($activeTab === 'paiements'): ?>
    <script src="assets/js/paiements.js"></script>
    <?php elseif ($activeTab === 'conjoints'): ?>
    <script src="assets/js/conjoints.js"></script>
    <?php elseif ($activeTab === 'rapports'): ?>
    <script src="assets/js/rapports.js"></script>
    <?php endif; ?>
</body>
</html>