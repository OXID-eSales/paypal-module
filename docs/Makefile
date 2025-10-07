svg:
	docker run --rm -v $$(pwd):/workspace plantuml/plantuml -tsvg /workspace/UML/*.puml -o /workspace/SVG/

.PHONY: svg clean

clean:
	rm -f SVG/*.svg

help:
	@echo "Available targets:"
	@echo "  svg    - Generate SVG files from all PUML diagrams"
	@echo "  clean  - Remove all generated SVG files"
	@echo "  help   - Show this help message"
