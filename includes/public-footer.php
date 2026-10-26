<?php
$pageScripts = $pageScripts ?? array();
$useBootstrap = $useBootstrap ?? true;
?>
    <footer class="container1 site-footer">
        <div class="footer site-footer-grid">
            <div class="footer-col site-footer-col footer-brand">
                <img src="<?php echo siteEscape(siteImage('branding.logo')); ?>" alt="Foodera logo">
                <p><?php echo siteEscape(siteSetting('footer.tagline')); ?></p>
                <a class="footer-cta" href="menu.php"><?php echo siteEscape(siteSetting('footer.cta')); ?></a>
            </div>

            <div class="footer-col site-footer-col">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="home.php">Home</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="offers.php">Offers</a></li>
                    <li><a href="about-us.php">About Us</a></li>
                    <li><a href="gallery.php">Gallery</a></li>
                    <li><a href="contact-us.php">Contact</a></li>
                    <li><a href="blog.php">Blogs</a></li>
                </ul>
            </div>

            <div class="footer-col site-footer-col">
                <h3>Account</h3>
                <ul>
                    <li><a href="order-history.php">My Orders</a></li>
                    <li><a href="profile.php">My Account</a></li>
                    <li><a href="track-order.php">Track delivery</a></li>
                    <li><a href="cancellation-refund-policy.php">Cancellation policy</a></li>
                    <li><a href="login.php">Login / Register</a></li>
                </ul>
            </div>

            <div class="footer-col site-footer-col">
                <h3>Contact</h3>
                <ul class="footer-contact">
                    <li><i class="bi bi-telephone-fill" aria-hidden="true"></i><span><?php echo siteEscape(siteSetting('footer.contact_1')); ?></span></li>
                    <li><i class="bi bi-receipt" aria-hidden="true"></i><span><?php echo siteEscape(siteSetting('footer.contact_2')); ?></span></li>
                    <li><i class="bi bi-shield-check" aria-hidden="true"></i><span><?php echo siteEscape(siteSetting('footer.contact_3')); ?></span></li>
                </ul>
                <h3 class="footer-follow-title">Follow Us</h3>
                <div class="social-links">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Twitter"><i class="bi bi-twitter" aria-hidden="true"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram" aria-hidden="true"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin" aria-hidden="true"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> Foodera. All rights reserved.</p>
            <div>
                <a href="terms.php">Terms</a>
                <a href="privacy.php">Privacy</a>
                <a href="ethical-use-policy.php">Ethical use</a>
            </div>
        </div>
    </footer>
    <button class="public-back-to-top" type="button" aria-label="Back to top" title="Back to top" hidden><i class="bi bi-arrow-up" aria-hidden="true"></i></button>

    <div class="modal fade foodera-legal-modal" id="terms-modal" tabindex="-1" aria-labelledby="terms-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h2 class="modal-title" id="terms-modal-title">Terms of Service</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="legal-effective">Effective date: 24 October 2026</p>
                    <p>These Terms of Service govern your access to Foodera's website, account, menu, ordering, delivery, and support services. By creating an account, placing an order, or using the service, you agree to these terms.</p>
                    <h3>1. Accounts and accurate information</h3>
                    <p>You are responsible for keeping your account credentials confidential and for the activity under your account. You must provide correct and current name, phone, email, address, and payment information. Foodera may suspend accounts or orders that appear fraudulent, abusive, or unsafe.</p>
                    <h3>2. Menu, prices, and availability</h3>
                    <p>Menu items, images, ingredients, prices, taxes, offers, and availability can change without notice. Product images are illustrative. Please review allergen and dietary information with the restaurant before ordering; Foodera cannot guarantee that a dish is allergen-free.</p>
                    <h3>3. Placing and accepting orders</h3>
                    <p>Submitting an order is a request to purchase, not acceptance by Foodera. An order is accepted when the restaurant confirms it. We may decline or cancel an order because of unavailable items, delivery-area limits, pricing errors, suspected fraud, or operational constraints. We will notify you where practical.</p>
                    <h3>4. Payments and billing</h3>
                    <p>Payment methods available at checkout may include cash on delivery, UPI, card, or net banking. Online payments are processed by the applicable payment provider. Foodera does not store full card numbers or CVV details. You authorise the applicable charge for your confirmed order, including applicable taxes, delivery fees, and any valid adjustments.</p>
                    <h3>5. Offers and coupons</h3>
                    <p>Offers are subject to their published eligibility, schedule, menu targeting, minimum value, usage limits, and coupon conditions. Unless an offer expressly says otherwise, one non-combinable offer applies per order. An offer may be removed when its conditions are no longer met after an item change or cancellation.</p>
                    <h3>6. Delivery and collection</h3>
                    <p>Delivery is available only to supported locations and pincodes. You must provide a complete, accessible delivery address and remain reachable at the supplied phone number. Delivery estimates are estimates, not guarantees. If delivery cannot be completed due to an incorrect address, no response, or unsafe access, additional charges or cancellation rules may apply.</p>
                    <h3>7. Cancellations, changes, and refunds</h3>
                    <p>Cancellation and dish-removal requests must be submitted before preparation begins and require a reason. Requests are reviewed by the restaurant and are not approved automatically. Approved refunds, where applicable, are processed to the original online payment method or handled according to the selected payment method and the <a href="cancellation-refund-policy.php">Cancellation and Refund Policy</a>. Delivery fees may remain payable for a partial cancellation.</p>
                    <h3>8. Acceptable use</h3>
                    <p>You must not misuse the service, make fraudulent orders, interfere with the site, scrape data, submit harmful content, or access another person's account or data. We may take action needed to protect customers, partners, and the service.</p>
                    <h3>9. Service availability and liability</h3>
                    <p>Foodera aims to provide a reliable service but cannot promise uninterrupted or error-free availability. To the extent permitted by law, Foodera is not liable for indirect, incidental, or consequential losses. Nothing in these terms limits rights that cannot legally be excluded.</p>
                    <h3>10. Changes and contact</h3>
                    <p>We may update these terms when the service or legal requirements change. The updated effective date will be shown here. For questions about these terms or an order, contact Foodera support using the details shown on the site.</p>
                </div>
                <div class="modal-footer"><button type="button" class="foodera-legal-close" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <div class="modal fade foodera-legal-modal" id="privacy-modal" tabindex="-1" aria-labelledby="privacy-modal-title" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h2 class="modal-title" id="privacy-modal-title">Privacy Notice</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body">
                    <p class="legal-effective">Effective date: 24 October 2026</p>
                    <p>This Privacy Notice explains how Foodera collects, uses, shares, protects, and retains personal information when you use our website and food ordering services.</p>
                    <h3>1. Information we collect</h3>
                    <p>Depending on how you use Foodera, we may collect your name, email address, phone number, password hash, saved address details, delivery instructions, order history, item preferences, support messages, offer usage, and device or browser information needed to operate and secure the site.</p>
                    <h3>2. Location information</h3>
                    <p>Foodera requests your device location only after you choose <strong>Use current location</strong>. Location permission is controlled by your browser. We use the coordinates only to help prepare a delivery address and validate serviceability. You can refuse permission, use manual entry instead, and review the suggested address before saving it. We do not continuously track your location.</p>
                    <h3>3. Why we use information</h3>
                    <p>We use information to create and secure accounts, process and deliver orders, calculate delivery eligibility and fees, apply offers, process payments, prevent fraud, respond to support requests, maintain order records, and improve our service.</p>
                    <h3>4. Payments</h3>
                    <p>Online payments are handled by payment providers. Foodera does not store complete card numbers, CVV codes, or banking credentials. Payment providers process information under their own privacy notices and security practices.</p>
                    <h3>5. When we share information</h3>
                    <p>We share the information needed to fulfil your order with the restaurant, delivery personnel or delivery partner, and payment provider. We may also share information with service providers that support hosting, security, communications, or analytics, subject to appropriate safeguards. We do not sell personal information.</p>
                    <h3>6. Retention</h3>
                    <p>We retain account and order information for as long as needed to provide the service, resolve issues, meet legal, tax, fraud-prevention, and record-keeping obligations. Cancelled orders and refunds remain in order history so that records are accurate.</p>
                    <h3>7. Your choices and rights</h3>
                    <p>You may update account details, remove saved addresses, manage browser location permission, and choose whether to save an address. You may request access, correction, or deletion of eligible personal information by contacting Foodera support. Some information may need to be retained for legal, security, or transaction-record purposes.</p>
                    <h3>8. Security</h3>
                    <p>We use reasonable administrative, technical, and organisational measures to protect personal information. No online system is completely secure, so please use a strong password and keep it private.</p>
                    <h3>9. Children</h3>
                    <p>Foodera is not intended for children who cannot legally enter into an online purchase contract. Do not provide personal information for a child without the consent of a parent or guardian where required by law.</p>
                    <h3>10. Updates and contact</h3>
                    <p>We may revise this notice when our practices or legal obligations change. The effective date will be updated above. For privacy questions or requests, contact Foodera support using the details shown on the site.</p>
                </div>
                <div class="modal-footer"><button type="button" class="foodera-legal-close" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    <?php if ($useBootstrap) { ?>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <?php } ?>
    <?php foreach ($pageScripts as $scriptPath) { ?>
        <script src="<?php echo htmlspecialchars($scriptPath); ?>?v=<?php echo (int) @filemtime(__DIR__ . '/../' . ltrim($scriptPath, '/')); ?>"></script>
    <?php } ?>
    <script src="js/theme-toggle.js"></script>
<script src="js/public-animations.js?v=<?php echo (int) @filemtime(__DIR__ . '/../js/public-animations.js'); ?>"></script>
<script src="js/table-pagination.js"></script>
<script>
(function () {
    const button = document.querySelector('.public-back-to-top');
    if (!button) return;
    function update() { button.hidden = window.scrollY < 420; }
    button.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    window.addEventListener('scroll', update, { passive: true });
    update();
}());
</script>
</body>
</html>
