# Number Counter Studio PRO 2.0

A modern, high-performance animated number counter video generator built with PHP, JavaScript, and FFmpeg. Generate animated count-up and countdown videos with lossless transparent PNG-in-MP4 alpha channel for direct import into video editors like CapCut, Adobe Premiere Pro, DaVinci Resolve, and Final Cut Pro.

## ✨ Features
- **High-FPS Counter Animation**: Smooth counting with configurable FPS (24, 30, 60 FPS), duration, and easing curves (Linear, Ease-In, Ease-Out, Ease-In-Out).
- **Lossless Transparent MP4 Alpha**: Encodes with PNG codec and 32-bit RGBA inside an MP4 container for native alpha overlay in video editors.
- **Custom Typography**: Supports local system fonts, 35 curated creator fonts, and Google Fonts.
- **Visual FX Engine**:
  - Linear multi-stop gradients or solid colors.
  - Crisp border outline stroke.
  - 3D extrusion depth with customizable direction angles.
  - Soft 2D Gaussian glow with precise spread and intensity control.
- **Pro NLE Monitor Preview**: Real-time canvas preview with transparency background mode switcher (Dark Alpha, Light Alpha, Black, White) and framing safe-area grid guides.

## 🚀 Getting Started

### Prerequisites
- PHP 7.4+ or PHP 8.x with GD extension enabled (`php_gd`).
- [FFmpeg](https://ffmpeg.org/download.html) installed on your system.
- Web server (e.g., Apache / XAMPP, Nginx, or PHP built-in server).

### Configuration
Open `config.php` and set the path to your FFmpeg binary:
```php
$ffmpegPath = 'C:/path/to/ffmpeg.exe'; // or 'ffmpeg' if in system PATH
```

### Running Locally
Place the project inside your web root (e.g. `htdocs/`) and navigate to:
```
http://localhost/countNumber/
```
or run with PHP's built-in server:
```bash
php -S localhost:8000
```
