<?php
class ModelExtensionTltblogTltblog extends Model {
	public function getTltBlog($tltblog_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "tltblog b LEFT JOIN " . DB_PREFIX . "tltblog_description bd ON (b.tltblog_id = bd.tltblog_id) LEFT JOIN " . DB_PREFIX . "tltblog_to_store b2s ON (b.tltblog_id = b2s.tltblog_id) WHERE b.tltblog_id = '" . (int)$tltblog_id . "' AND bd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND b.status = '1' AND b2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		return $query->row;
	}

	public function getTltBlogs($limit = 0, $where_tags = array(), $sort = 1, $start = 0, $show_in_sitemap = true) {
		// ...

		return $query->rows;
	}

	public function getTltBlogsBottom() {
		// ...

		return $blogs;
	}

	/* Get only tags, which are linked to any blog entry */
	
	public function getTltTags() {
		// ...

		return $tags;
	}

	// ...

	public function getTotalTltTags() {
		// ...
		
		return $count;
	}

	// Add, Edit or delete review approval
	public function handleReviewApproval($tltblog_id, $customer_id, $data) {
		$approval = filter_var($data['approval'], FILTER_VALIDATE_BOOLEAN);
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "blog_review_approval WHERE review_id = '" . $data['review_id'] . "' AND customer_id = '" . $customer_id . "'");
		
		$total = false;
		$result = array();

		// Get total number before
		$total = $this->db->query("SELECT approval, disapproval FROM " . DB_PREFIX . "blog_review WHERE review_id = '" . $data['review_id'] . "' AND tltblog_id = '" . $tltblog_id . "'");
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
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET approval = '" . ($total['approval']+1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET disapproval = '" . ($total['disapproval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review_approval SET approval = '" . (int)$approval . "' WHERE review_id = '" . (int)$data['review_id']  . "' AND customer_id = '" . (int)$customer_id . "'");
					$result['class'] = 'approve';
					$result['change'] = true;
				} else if (!$approval && $query->rows[0]['approval'] == '1') {
					$result['v'] = 2;
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET approval = '" . ($total['approval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET disapproval = '" . ($total['disapproval']+1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review_approval SET approval = '" . (int)$approval . "' WHERE review_id = '" . (int)$data['review_id']  . "' AND customer_id = '" . (int)$customer_id . "'");
					$result['class'] = 'disapprove';
					$result['change'] = true;
				} else {
					$same_c = true;
				}
			}
			if (($data['checked'] != '1' && $total) || ($data['checked'] == '1' && $same_c)) {
				// DELETE row in `..review_approval` table and update number in `..review` table
				if ($approval) {
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET approval = '" . ($total['approval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
				} else {
					$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET disapproval = '" . ($total['disapproval']-1) . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
				}
				$this->db->query("DELETE FROM `" . DB_PREFIX . "blog_review_approval` WHERE review_id = '" . (int)$data['review_id'] . "' AND customer_id = '" . (int)$customer_id . "'");
				$result['class'] = ($approval ? 'approve' : 'disapprove');
				$result['cancel'] = true;
				$result['update'] = ($same_c ? true : NULL);
			}
		} else {
			// Insert new records
			if ($approval) {
				$data['checked'] == '1' ? $total['approval'] += 1 : $total['approval'] -= 1; // NOTE: the case '.. -= 1' may never occur
				$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET approval = '" . $total['approval'] . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
			} else {
				$data['checked'] == '1' ? $total['disapproval'] += 1 : $total['disapproval'] -= 1;
				$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET disapproval = '" . $total['disapproval'] . "' WHERE review_id = '" . (int)$data['review_id'] . "' AND tltblog_id = '" . (int)$tltblog_id . "'");
			}

			if ($data['checked'] == '1') {
				$this->db->query("INSERT INTO " . DB_PREFIX . "blog_review_approval SET customer_id = '" . (int)$customer_id . "', approval = '" . (int)$approval . "', review_id = '" . (int)$data['review_id'] . "'");
				$result['class'] = ($approval ? 'approve' : 'disapprove');
			}
		}
		return $result;
	}

	public function addReview($tltblog_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "blog_review SET author = '" . $this->db->escape($data['name']) . "', customer_id = '" . (int)$this->customer->getId() . "', tltblog_id = '" . (int)$tltblog_id . "', text = '" . $this->db->escape($data['text']) . "', depth = '" . (int)$data['depth'] . "', related = '" . (isset($data['related']) ? (int)$data['related'] : '0') . "', date_added = NOW(), approval = '0', disapproval = '0'");
	}

	public function modifyReview($tltblog_id, $review_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "blog_review SET author = '" . $this->db->escape($data['name']) . "', date_modified = NOW(), text = '" . $this->db->escape($data['text']) . "' WHERE tltblog_id = '" . (int)$tltblog_id . "' AND customer_id = '" . (int)$this->customer->getId() . "' AND review_id = '" . (int)$review_id . "'");
	}

	public function getReviewsByProductId($product_id) {
		// ...
		return $query->rows;
	}

	public function getTotalReviewsByProductId($tltblog_id) {
		// ...
		return $query->row['total'];
	}
}
