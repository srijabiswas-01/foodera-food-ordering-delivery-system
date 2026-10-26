<?php
include('app-functions.php');
require_once __DIR__ . '/includes/site-content.php';
session_start();
$isLoggedIn = isset($_SESSION['user']);
$count = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
$pageTitle = 'Contact Us';
$activePage = 'contact';
$bodyClass = 'contact-page';
$pageStyles = array('css/contact.css');
include('includes/public-header.php');
?>

     <main class="contact-main">
         <section class="contact-hero">
            <p>Contact Foodera</p>
            <h1><?php echo siteEscape(siteSetting('contact.title')); ?></h1>
            <span><?php echo siteEscape(siteSetting('contact.intro')); ?></span>
         </section>

         <section class="contact-box">
             <div class="left contact-form-panel">
                <h2><?php echo siteEscape(siteSetting('contact.form_title')); ?></h2>
                <p><?php echo siteEscape(siteSetting('contact.form_intro')); ?></p>
                <form action="" method="POST">
                    <div class="contact-row">
                        <input type="text" name="fname" placeholder="First Name" required>
                        <input type="text" name="lname" placeholder="Last Name">
                    </div>
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="text" name="subject" placeholder="Subject of this message">
                    <textarea name="message" placeholder="Message" rows="5" required></textarea>
                    <button class="submit" name="submit">Send Message</button>
                </form>
                    <?php
                    if(isset($_POST['submit'])){
                        $res=sendmessage($_POST);
                        $ticket = $res ? createSupportTicket(trim($_POST['email'] ?? ''), trim($_POST['subject'] ?? 'General enquiry') ?: 'General enquiry', trim($_POST['message'] ?? '')) : false;
                        if($res==1)
                        echo " <p class='contact-alert success'>Message sent successfully! Your support reference is " . htmlspecialchars($ticket ?: 'being created', ENT_QUOTES, 'UTF-8') . ".</p>";
                        else
                        echo "<p class='contact-alert error'>Message not sent.</p>";    
                    }
                    ?>
        </div>
             <div class="right contact-info-panel">
  
                 <img src="<?php echo siteEscape(siteImage('contact.map')); ?>" alt="Foodera service area">
                 <div class="contact-info-list">
                    <div><i class="bi bi-geo-alt" aria-hidden="true" id="map"></i><span class="form-info"><?php echo siteEscape(siteSetting('contact.address')); ?></span></div>
                    <div><i class="bi bi-telephone" aria-hidden="true" id="call"></i><span class="form-info"><?php echo siteEscape(siteSetting('contact.phone')); ?></span></div>
                    <div><i class="bi bi-envelope" aria-hidden="true" id="msg"></i><span class="form-info"><?php echo siteEscape(siteSetting('contact.email')); ?></span></div>
                 </div>
        </div>
         </section>
     </main>

<?php include('includes/public-footer.php'); ?>
