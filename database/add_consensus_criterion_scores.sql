-- Per-criterion scores for consensus peer review (JSON: {"tc_id": score, ...})
ALTER TABLE `peer_review_individual_score`
  ADD COLUMN `criterion_scores` JSON NULL COMMENT 'Per-criterion scores before consensus' AFTER `score`;
