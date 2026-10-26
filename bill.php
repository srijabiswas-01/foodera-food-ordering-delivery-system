<?php
include('app-functions.php');
session_start();
if(!isset($_SESSION['user']))
{
    header('location:login.php');
    exit();
}

$order = null;
if (isset($_SESSION['last_order_id'])) {
    $items = getOrderItems((int)$_SESSION['last_order_id']);
    $conn = dbConnect();
    $stmt = mysqli_prepare($conn, 'SELECT * FROM `orders` WHERE orderid = ?');
    mysqli_stmt_bind_param($stmt, 'i', $_SESSION['last_order_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $order = $result ? mysqli_fetch_assoc($result) : null;
} else {
    $order = getLatestOrderByEmail($_SESSION['email']);
    $items = $order ? getOrderItems((int)$order['orderid']) : false;
}

$invoiceDate = date('F d, Y');
$total = 0;
$pageTitle = 'Bill';
$activePage = 'cart';
$bodyClass = 'bill-page';
include('includes/public-header.php');
?>
		<style>
			.invoice-box {
				max-width: 800px;
				margin: auto;
				padding: 30px;
				border: 1px solid #eee;
				box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
				font-size: 16px;
				line-height: 24px;
				font-family: 'Poppins', Arial, Helvetica, sans-serif;
				background: var(--surface);
				color: var(--text);
			}

			.invoice-box table {
				width: 100%;
				line-height: inherit;
				text-align: left;
			}

			.invoice-box table td {
				padding: 5px;
				vertical-align: top;
			}

			.invoice-box table tr td:nth-child(2) {
				text-align: right;
			}

			.invoice-box table tr.top table td {
				padding-bottom: 20px;
			}

			.invoice-box table tr.top table td.title {
				font-size: 30px;
				line-height: 36px;
				color: var(--text);
			}

			.invoice-box table tr.information table td {
				padding-bottom: 40px;
			}

			.invoice-box table tr.heading td {
				background: var(--bg);
				border-bottom: 1px solid #ddd;
				font-weight: bold;
			}

			.invoice-box table tr.details td {
				padding-bottom: 20px;
			}

			.invoice-box table tr.item td {
				border-bottom: 1px solid #eee;
			}

			.invoice-box table tr.item.last td {
				border-bottom: none;
			}

			.invoice-box table tr.total td:nth-child(2) {
				border-top: 2px solid #eee;
				font-weight: bold;
			}
			.heading2 {
                color: var(--primary);
                margin-bottom: 30px;
                padding: 5px 0;
                grid-column: 1/-1;
                text-align: center;
            }
			.heading2 button {
                background-color: var(--primary);
                border: none;
                color: #ffffff;
                font-size: 16px;
                font-weight: 600;
                border-radius: 5px;
                min-width: 130px;
                min-height: 40px;
                transition: 0.2s all;
                margin: 10px;
				padding: 10px 16px;
            }
			
            .heading1 {
                color: var(--primary);
                margin-bottom: 30px;
                padding: 30px 0;
                grid-column: 1/-1;
                text-align: center;
            }
			.heading1 button {
                background-color: var(--secondary);
                border: none;
                color: #ffffff;
                font-size: 16px;
                font-weight: 600;
                border-radius: 5px;
                min-width: 130px;
                min-height: 40px;
                transition: 0.2s all;
                margin: 10px;
				padding: 10px 16px;
            }
			.invoice-box button:hover {
                transform: scale(1.02);
            }
			@media only screen and (max-width: 600px) {
				.invoice-box table tr.top table td {
					width: 100%;
					display: block;
					text-align: center;
				}

				.invoice-box table tr.information table td {
					width: 100%;
					display: block;
					text-align: center;
				}
			}

			/** RTL **/
			.invoice-box.rtl {
				direction: rtl;
				font-family: Tahoma, 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
			}

			.invoice-box.rtl table {
				text-align: right;
			}

			.invoice-box.rtl table tr td:nth-child(2) {
				text-align: left;
			}
		</style>
		<div class="invoice-box">
			<table cellpadding="0" cellspacing="0">
				<tr class="top">
					<td colspan="2">
						<table>
							<tr>
								<td class="title">
					<img src="<?php echo siteEscape(siteImage('branding.logo')); ?>" style="width: 150px; max-width: 100%" alt="Foodera" />
								</td>

								<td>
									Invoice #:FE <?php echo $order ? str_pad($order['orderid'], 6, '0', STR_PAD_LEFT) : '000000'; ?><br />
									Created: <?php echo $invoiceDate; ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<tr class="information">
					<td colspan="2">
						<table>
							<tr>
								<td>
									Foodera, Inc.<br />
									658 Salt Lake City Block Sector 1,<br />
									Kolkata-700064
								</td>

								<td>
								<?php if ($order) { ?>
								<p>Billing name: <?php echo htmlspecialchars($order['name']); ?></p>
								<p>Billing email: <?php echo htmlspecialchars($order['email']); ?></p>
								<p>Billing phone: <?php echo htmlspecialchars($order['phone']); ?></p>
								<p>Billing address: <?php echo htmlspecialchars($order['address']); ?></p>
								<?php } else { ?>
								<p>No order found for this invoice.</p>
								<?php } ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>

				<tr class="heading">
					<td>Payment Method</td>
<td></td>
<td></td>
				</tr>

				<tr class="details">
<td>credit</td>
<td></td>
					<td>Done</td>
				</tr>

    <tr class="heading">
      <td>Item</td>
	  <td>Quantity</td>
	  <td>Price</td>
    </tr>
	<?php if ($items && mysqli_num_rows($items) > 0) {
        while ($item = mysqli_fetch_assoc($items)) {
            $lineTotal = (int)$item['pprice'] * (int)$item['quantity'];
            $total += $lineTotal;
    ?>
				<tr class="item">
					<td><?php echo htmlspecialchars($item['pname']); ?></td>
                    <td><?php echo htmlspecialchars($item['quantity']); ?></td>
					<td>Rs <?php echo number_format($lineTotal, 2); ?></td>
				</tr>
	<?php }
    } else { ?>
                <tr class="item">
                    <td colspan="3">No items found.</td>
                </tr>
    <?php } ?>


				<tr class="total">
					<td></td>
					<td></td>

					<td>Total: Rs <?php echo number_format($total, 2); ?></td>
				</tr>
				
			</table>
			<div class="heading2 ">
        <button onclick="download() ">Print</button>
    </div>
			<div class="heading1 ">
        <button onclick="previous() ">Back to home</button>
    </div>
		</div>
		<script>
        function previous() {

            window.location.href = "home.php";

        }
		function download() {

window.print();

}
    </script>
<?php include('includes/public-footer.php'); ?>
