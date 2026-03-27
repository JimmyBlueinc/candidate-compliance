
import React from 'react';

export interface FeatureCardProps {
  title: string;
  description: string;
  items?: string[];
  icon?: React.ReactNode;
  imageUrl?: string;
  className?: string;
}

export interface NavLinkProps {
  label: string;
  href: string;
}
