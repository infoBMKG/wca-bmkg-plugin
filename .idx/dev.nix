{ pkgs, ... }: {
  channel = "stable-24.11";

  packages = [
    pkgs.php
  ];
}
