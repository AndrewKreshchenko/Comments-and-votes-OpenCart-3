<?php

function array_find($array, $i, $val) {
	if(is_array($array) && count($array)>0) {
		foreach(array_keys($array) as $key){
			$temp[$key] = $array[$key][$i];
			
			if ($temp[$key] == $val){
				$newarray[$key] = $array[$key];
			}
		}
	}
	return $newarray;
}

class ControllerExtensionTltBlogTltBlog extends Controller {
	public function index() {
		$this->load->language('extension/tltblog/tltblog');

		$this->load->model('extension/tltblog/tltblog');
		$this->load->model('catalog/product');
		$this->load->model('setting/setting');
		$this->load->model('tool/image');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);

        if ($this->config->has('tltblog_path')) {
            $path_array = $this->config->get('tltblog_path');
        }

        if (isset($this->request->get['tltpath'])) {
            $path = $this->request->get['tltpath'];
        } elseif (isset($path_array[$this->config->get('config_language_id')])) {
            $path = $path_array[$this->config->get('config_language_id')];
        } else {
            $path = 'blogs';
        }
		
		$data['show_path'] = $this->config->get('tltblog_show_path');

		if ($data['show_path']) {
			if ($this->config->has('tltblog_path_title')) {
				$tmp_title = $this->config->get('tltblog_path_title');
				$root_title = $tmp_title[$this->config->get('config_language_id')]['path_title'];
			} else {
				$root_title = $this->language->get('text_title');
			}
			
			$data['breadcrumbs'][] = array(
				'text' => $root_title,
				'href' => $this->url->link('extension/tltblog/tlttag', 'tltpath=' . $path)
			);
		}

		if (isset($this->request->get['tltblog_id'])) {
			$tltblog_id = (int)$this->request->get['tltblog_id'];
		} else {
			$tltblog_id = 0;
		}

		$tltblog_info = $this->model_extension_tltblog_tltblog->getTltBlog($tltblog_id);

		if ($tltblog_info) {
			$this->document->setTitle($tltblog_info['meta_title']);
			$this->document->setDescription($tltblog_info['meta_description']);
			$this->document->setKeywords($tltblog_info['meta_keyword']);
            $this->document->addScript('catalog/view/javascript/jquery/magnific/jquery.magnific-popup.min.js');
			$this->document->addStyle('catalog/view/javascript/jquery/magnific/magnific-popup.css');
			$this->document->addStyle('catalog/view/theme/formakh/stylesheet/blog.css');

			$this->document->addLink($this->url->link('extension/tltblog/tltblog', 'tltpath=' . $path . '&tltblog_id=' . $tltblog_id), 'canonical');

			$data['breadcrumbs'][] = array(
				'text' => $tltblog_info['title'],
				'href' => $this->url->link('extension/tltblog/tltblog', 'tltpath=' . $path . '&tltblog_id=' .  $tltblog_id)
			);

			$data['heading_title'] = $tltblog_info['title'];
			$data['show_title'] = $tltblog_info['show_title'];

			if ($tltblog_info['image']) {
				if ($this->request->server['HTTPS']) {
					$data['blog_image'] = $this->config->get('config_ssl') . 'image' . $tltblog_info['image'];
				} else {
					$data['blog_image'] = $this->config->get('config_url') . 'image' . $tltblog_info['image'];
				}
			} else {
				$data['blog_image'] = '';
			}

			$data['description'] = html_entity_decode($tltblog_info['description'], ENT_QUOTES, 'UTF-8');
			$data['intro'] = strip_tags(html_entity_decode($tltblog_info['intro'], ENT_NOQUOTES, 'UTF-8'));
			$data['meta_description'] = $tltblog_info['meta_description'];
			$data['button_cart'] = $this->language->get('button_cart');
			$data['button_wishlist'] = $this->language->get('button_wishlist');
			$data['button_compare'] = $this->language->get('button_compare');
			$data['text_related'] = $this->language->get('text_related');
			$data['text_tags'] = $this->language->get('text_tags');
			$data['text_tax'] = $this->language->get('text_tax');
			$data['text_compare'] = sprintf($this->language->get('text_compare'), (isset($this->session->data['compare']) ? count($this->session->data['compare']) : 0));
			$data['tltblog_id'] = $tltblog_id;

			$data['products'] = array();

			$tltblog_relateds = $this->model_extension_tltblog_tltblog->getTltBlogRelated($tltblog_id);

			foreach ($tltblog_relateds as $tltblog_related) {
				$result = $this->model_catalog_product->getProduct($tltblog_related["related_id"]);
				
				if ($result['image']) {
                    $image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				} else {
                    $image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}

				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get($this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			$data['tags'] = array();
			
			$tltblog_tags = $this->model_extension_tltblog_tltblog->getTltTagsForBlog($tltblog_id);

			foreach ($tltblog_tags as $tltblog_tag) {
				$data['tags'][] = array(
					'title' => $tltblog_tag['title'],
					'href'  => $this->url->link('extension/tltblog/tlttag', 'tltpath=' . $path . '&tlttag_id=' . $tltblog_tag['tlttag_id'])
				);
			}

			// Reviews START
			$data['reviews'] = array();

			$reviews_total = $this->model_extension_tltblog_tltblog->getTotalReviewsByProductId($this->request->get['tltblog_id']);

			if ($reviews_total) {
				$data['total'] = $reviews_total;
				$results = $this->model_extension_tltblog_tltblog->getReviewsByProductId($this->request->get['tltblog_id']);
				$related_ids = array();

				foreach ($results as $result) {
					if ((int)$result['related'] > 1) {
						array_push($related_ids, (int)$result['related']);
					}
				}

				$ignored = array();
				
				// 1st level
				foreach ($results as $result) {
					if (in_array($result['review_id'], $ignored)) {
						continue;
					}
					$data['reviews'][] = array(
						'id'		 => $result['review_id'],
						'author'     => $result['author'],
						'text'       => nl2br($result['text']),
						'depth'      => $result['depth'],
						'related'	 => $result['related'],
						'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
						'own'		 => ($result['review_id'] == $this->customer->getId() ? 1 : NULL),
						'approval'   => ($result['approval'] !== '0' ? $result['approval'] : NULL),
						'disapproval' => ($result['disapproval'] !== '0' ? $result['disapproval'] : NULL)
					);
					if (in_array($result['review_id'], $related_ids)) {
						
						$found = array_find($results, 'related', $result['review_id']);
						
						// 2d level
						foreach ($found as $item) {
							if (in_array($item['review_id'], $ignored)) {
								continue;
							}
							
							$data['reviews'][] = array(
								'id'		 => $item['review_id'],
								'author'     => $item['author'],
								'text'       => nl2br($item['text']),
								'depth'      => $item['depth'],
								'related'	 => $item['related'],
								'date_added' => date($this->language->get('date_format_short'), strtotime($item['date_added'])),
								'own'		 => ($result['review_id'] == $this->customer->getId() ? 1 : NULL),
								'approval'   => ($item['approval'] !== '0' ? $item['approval'] : NULL),
								'disapproval' => ($item['disapproval'] !== '0' ? $item['disapproval'] : NULL)
							);

							// 3d level
							if (in_array($item['review_id'], $related_ids)) {
								$found2 = array_find($results, 'related', $item['review_id']);
								foreach ($found2 as $item2) {
									$data['reviews'][] = array(
										'id'		 => $item2['review_id'],
										'author'     => $item2['author'],
										'text'       => nl2br($item2['text']),
										'depth'      => $item2['depth'],
										'related'	 => $item2['related'],
										'date_added' => date($this->language->get('date_format_short'), strtotime($item2['date_added'])),
										'own'		 => ($result['review_id'] == $this->customer->getId() ? 1 : NULL),
										'approval'   => ($item2['approval'] !== '0' ? $item2['approval'] : NULL),
										'disapproval' => ($item2['disapproval'] !== '0' ? $item2['disapproval'] : NULL)
									);
									array_push($ignored, $item2['review_id']);
								}
							}
							array_push($ignored, $item['review_id']);
						}
					}
				}
				// Reviews END
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
				$data['text_login'] = sprintf($this->language->get('text_login'), $this->url->link('account/login', '', true), $this->url->link('account/register', '', true));
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$data['logged'] = $this->customer->isLogged();

			$this->response->setOutput($this->load->view('extension/tltblog/tltblog', $data));
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('extension/tltblog/tltblog', 'tltpath=' . $path . '&tltblog_id=' . $tltblog_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('error/not_found', $data));
		}
	}

	// Add approval or disapproval to review
	public function approval() {
		$this->load->language('extension/tltblog/tltblog');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if (isset($this->request->post['approval'])) {
				$this->load->model('extension/tltblog/tltblog');
				$json = $this->model_extension_tltblog_tltblog->handleReviewApproval($this->request->get['tltblog_id'], $this->customer->getId(), $this->request->post);

				if (isset($json['cancel'])) {
					$json['success'] = $this->language->get('text_success_cancel_comment_approval');
				}
				else {
					if ($this->request->post['approval'] == '1') {
						$json['success'] = $this->language->get('text_success_approve_comment');
					} else {
						$json['success'] = $this->language->get('text_success_disapprove_comment');
					}
				}
			} else {
				$json['error'] = $this->language->get('error_vote_comment');
				$json['message'] = 'Error';
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// Add a review
	public function write() {
		$this->load->language('extension/tltblog/tltblog');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}

			if ((utf8_strlen($this->request->post['text']) < 4) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}

			if (!isset($json['error'])) {
				$this->load->model('extension/tltblog/tltblog');

				$this->model_extension_tltblog_tltblog->addReview($this->request->get['tltblog_id'], $this->request->post);

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	// Modify existing review
	public function modify() {
		$this->load->language('extension/tltblog/tltblog');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			if ((utf8_strlen($this->request->post['text']) < 4) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}

			if (!isset($json['error']) && isset($this->request->get['review_id'])) {
				$this->load->model('extension/tltblog/tltblog');

				$this->model_extension_tltblog_tltblog->modifyReview($this->request->get['tltblog_id'], $this->request->get['review_id'], $this->request->post);

				$json['success'] = $this->language->get('text_success');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
