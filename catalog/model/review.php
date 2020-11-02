<?php
class ModelCatalogReview extends Model {
	public function addReview($product_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "review SET author = '" . $this->db->escape($data['name']) . "', customer_id = '" . (int)$this->customer->getId() . "', product_id = '" . (int)$product_id . "', text = '" . $this->db->escape($data['text']) . "', rating = '" . (int)$data['rating'] . "', date_added = NOW()");

		$review_id = $this->db->getLastId();

		if (in_array('review', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/review');
			$this->load->model('catalog/product');
			
			$product_info = $this->model_catalog_product->getProduct($product_id);

			$subject = sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));

			$message  = $this->language->get('text_waiting') . "\n";
			$message .= sprintf($this->language->get('text_product'), html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8')) . "\n";
			$message .= sprintf($this->language->get('text_reviewer'), html_entity_decode($data['name'], ENT_QUOTES, 'UTF-8')) . "\n";
			$message .= sprintf($this->language->get('text_rating'), $data['rating']) . "\n";
			$message .= $this->language->get('text_review') . "\n";
			$message .= html_entity_decode($data['text'], ENT_QUOTES, 'UTF-8') . "\n\n";

			$mail = new Mail($this->config->get('config_mail_engine'));
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject($subject);
			$mail->setText($message);
			$mail->send();

			// Send to additional alert emails
			$emails = explode(',', $this->config->get('config_mail_alert_email'));

			foreach ($emails as $email) {
				if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
					$mail->setTo($email);
					$mail->send();
				}
			}
		}
	}

	public function addReviewApproval($product_id, $customer_id, $data) {
		$approval = filter_var($data['approval'], FILTER_VALIDATE_BOOLEAN);
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "review_approval WHERE review_id = '" . $data['review_id'] . "' AND customer_id = '" . $customer_id . "'");
		
		$total = false;
		$result = array();

		// Get total number before
		$total = $this->db->query("SELECT approval, disapproval FROM " . DB_PREFIX . "review WHERE review_id = '" . $data['review_id'] . "' AND product_id = '" . $product_id . "'");
		$total = array(
			'approval' => (int)$total->row['approval'],
			'disapproval' => (int)$total->row['disapproval']
		);

		$result = array(
			'approval' => $approval,
			'id' => $data['review_id']
		);

		// If oc_review_approval contains records by criterion
		if ($query->rows) {
			// Check if a customer is going to cancel his own vote
			$same_c = false; 

			// Change existing records
			if ($data['checked'] == '1') {
				// If the same user checked , he did an attempt to make approval or disaproval
				// But the result shouldn't increment or decrement twice
				if ($approval && $query->rows[0]['approval'] == '0') {
					// If approval doesn't exist and user approves
					$this->db->query("UPDATE " . DB_PREFIX . "review SET approval = '" . ($total['approval']+1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "review SET disapproval = '" . ($total['disapproval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "review_approval SET approval = '" . (int)$approval . "' WHERE review_id = '" . (int)$data['review_id']  . "' AND customer_id = '" . (int)$customer_id . "'");
					$result['class'] = 'approve';
					$result['change'] = true;
				} else if (!$approval && $query->rows[0]['approval'] == '1') {
					$this->db->query("UPDATE " . DB_PREFIX . "review SET approval = '" . ($total['approval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "review SET disapproval = '" . ($total['disapproval']+1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "review_approval SET approval = '" . (int)$approval . "' WHERE review_id = '" . (int)$data['review_id']  . "' AND customer_id = '" . (int)$customer_id . "'");
					$result['class'] = 'disapprove';
					$result['change'] = true;
				} else {
					$same_c = true;
				}
			}
			if (($data['checked'] != '1' && $total) || ($data['checked'] == '1' && $same_c)) {
				// DELETE row in `..review_approval` table and update number in `..review` table
				if ($approval) {
					$this->db->query("UPDATE " . DB_PREFIX . "review SET approval = '" . ($total['approval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
				} else {
					$this->db->query("UPDATE " . DB_PREFIX . "review SET disapproval = '" . ($total['disapproval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
				}
				$this->db->query("DELETE FROM `" . DB_PREFIX . "review_approval` WHERE review_id = '" . (int)$data['review_id'] . "' AND customer_id = '" . (int)$customer_id . "'");
				$result['class'] = ($approval ? 'approve' : 'disapprove');
				$result['cancel'] = true;
				$result['update'] = ($same_c ? true : NULL);
			}
		} else {
			// Insert new records
			if ($approval) {
				$data['checked'] == '1' ? $total['approval'] += 1 : $total['approval'] -= 1; // NOTE: the case '.. -= 1' may never occur
				$this->db->query("UPDATE " . DB_PREFIX . "review SET approval = '" . $total['approval'] . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
			} else {
				$data['checked'] == '1' ? $total['disapproval'] += 1 : $total['disapproval'] -= 1;
				$this->db->query("UPDATE " . DB_PREFIX . "review SET disapproval = '" . $total['disapproval'] . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND product_id = '" . (int)$product_id . "'");
			}

			if ($data['checked'] == '1') {
				$this->db->query("INSERT INTO " . DB_PREFIX . "review_approval SET customer_id = '" . (int)$customer_id . "', approval = '" . (int)$approval . "', review_id = '" . (int)$data['review_id'] . "'");
				$result['class'] = ($approval ? 'approve' : 'disapprove');
			}
		}
		return $result;
	}

	public function getReviewsByProductId($product_id, $start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$query = $this->db->query("SELECT r.review_id, r.author, r.rating, r.text, r.approval, r.disapproval, p.product_id, pd.name, p.price, p.image, r.date_added FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product p ON (r.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND p.date_available <= NOW() AND p.status = '1' AND r.status = '1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY r.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);

		return $query->rows;
	}

	public function getTotalReviewsByProductId($product_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "review r LEFT JOIN " . DB_PREFIX . "product p ON (r.product_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) WHERE p.product_id = '" . (int)$product_id . "' AND p.date_available <= NOW() AND p.status = '1' AND r.status = '1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");

		return $query->row['total'];
	}
}