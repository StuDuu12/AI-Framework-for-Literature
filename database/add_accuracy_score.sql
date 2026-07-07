-- Migration: Add accuracy_score column to evaluate table
-- Date: 2026-03-20
-- Purpose: Support S_Acc formula for peer evaluation incentive system
--
-- Formula: S_Acc = max(0, S_Max - α * Δ)
--   where Δ = |S_Peer - S_Teacher|
--         S_Max = sum of max_score across all criteria in the task
--         α = 1 (linear penalty coefficient)
-- Final grade for evaluator: Score_Final = S_Perf + S_Acc

ALTER TABLE `evaluate`
  ADD COLUMN `accuracy_score` DECIMAL(6,2) DEFAULT NULL
    COMMENT 'S_Acc = max(0, S_Max - α*|S_Peer - S_Teacher|); NULL = teacher has not graded yet'
  AFTER `total_score`;
