-- Adiciona suporte a foto do produto/camisa nos depoimentos do CMS
ALTER TABLE cms_testimonials
ADD COLUMN IF NOT EXISTS product_image_path VARCHAR(255) NULL AFTER avatar_path;
